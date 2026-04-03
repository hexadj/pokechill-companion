<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Pokemon;
use App\ReferenceData\Dto\PokemonReferenceData;
use App\ReferenceData\Import\PokemonReferenceImporter;
use Doctrine\ORM\EntityManagerInterface;

final class PokemonReferenceImporterIntegrationTest extends IntegrationTestCase
{
    private function sample(
        string $key,
        string $name = 'Imported Mon',
        string $primaryTypeCode = 'fire',
        ?string $secondaryTypeCode = null,
    ): PokemonReferenceData {
        return new PokemonReferenceData(
            sourceKey: $key,
            name: $name,
            hp: 1,
            atk: 1,
            def: 1,
            satk: 1,
            sdef: 1,
            spe: 1,
            primaryTypeCode: $primaryTypeCode,
            secondaryTypeCode: $secondaryTypeCode,
            isActive: true,
        );
    }

    private function sampleWithStats(
        string $key,
        int $hp,
        int $atk,
        int $def,
        int $satk,
        int $sdef,
        int $spe,
        string $name = 'Imported Mon',
        string $primaryTypeCode = 'fire',
        ?string $secondaryTypeCode = null,
    ): PokemonReferenceData {
        return new PokemonReferenceData(
            sourceKey: $key,
            name: $name,
            hp: $hp,
            atk: $atk,
            def: $def,
            satk: $satk,
            sdef: $sdef,
            spe: $spe,
            primaryTypeCode: $primaryTypeCode,
            secondaryTypeCode: $secondaryTypeCode,
            isActive: true,
        );
    }

    public function testDryRunCountsWouldBeCreatedWithoutWrites(): void
    {
        $importer = static::getContainer()->get(PokemonReferenceImporter::class);
        $key = 'integration-dry-'.bin2hex(random_bytes(4));
        $r = $importer->import([$this->sample($key)], false, true);

        self::assertTrue($r['dryRunApplied']);
        self::assertSame(1, $r['created']);
        self::assertSame(0, $r['updated']);
        self::assertSame(0, $r['ignored']);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $found = $em->getRepository(Pokemon::class)->findOneBy(['sourceKey' => $key]);
        self::assertNull($found);
    }

    public function testCreatesThenIgnoresWhenUnchanged(): void
    {
        $importer = static::getContainer()->get(PokemonReferenceImporter::class);
        $key = 'integration-cr-'.bin2hex(random_bytes(4));
        $first = $this->sample($key);
        $r1 = $importer->import([$first], false, false);
        self::assertSame(1, $r1['created']);

        $r2 = $importer->import([$first], false, false);
        self::assertSame(0, $r2['created']);
        self::assertSame(0, $r2['updated']);
        self::assertSame(1, $r2['ignored']);
    }

    public function testUpdatesWhenNameChanges(): void
    {
        $importer = static::getContainer()->get(PokemonReferenceImporter::class);
        $key = 'integration-up-'.bin2hex(random_bytes(4));
        $importer->import([$this->sample($key, 'Old')], false, false);

        $r = $importer->import([$this->sample($key, 'New')], false, false);
        self::assertSame(1, $r['updated']);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $entity = $em->getRepository(Pokemon::class)->findOneBy(['sourceKey' => $key]);
        self::assertNotNull($entity);
        self::assertSame('New', $entity->getName());
    }

    public function testUpdatesWhenTypesChange(): void
    {
        $importer = static::getContainer()->get(PokemonReferenceImporter::class);
        $key = 'integration-type-'.bin2hex(random_bytes(4));
        $importer->import([$this->sample($key, 'Typed Mon', 'fire', null)], false, false);

        $result = $importer->import([$this->sample($key, 'Typed Mon', 'water', 'flying')], false, false);
        self::assertSame(1, $result['updated']);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $entity = $em->getRepository(Pokemon::class)->findOneBy(['sourceKey' => $key]);
        self::assertNotNull($entity);
        self::assertSame('water', $entity->getPrimaryType()->getCode());
        self::assertNotNull($entity->getSecondaryType());
        self::assertSame('flying', $entity->getSecondaryType()?->getCode());
    }

    public function testDeduplicatesUpstreamDuplicateSourceKeys(): void
    {
        $importer = static::getContainer()->get(PokemonReferenceImporter::class);
        $key = 'integration-dedup-'.bin2hex(random_bytes(4));
        $a = $this->sample($key, 'First');
        $b = $this->sample($key, 'Second');

        $r = $importer->import([$a, $b], false, false);
        self::assertSame(1, $r['deduplicated']);
        self::assertSame(1, $r['created']);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $entity = $em->getRepository(Pokemon::class)->findOneBy(['sourceKey' => $key]);
        self::assertNotNull($entity);
        self::assertSame('Second', $entity->getName());
    }

    public function testUnknownTypeCodeThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown type codes');

        $importer = static::getContainer()->get(PokemonReferenceImporter::class);
        $bad = new PokemonReferenceData(
            sourceKey: 'bad-type-'.bin2hex(random_bytes(4)),
            name: 'X',
            hp: 3,
            atk: 3,
            def: 3,
            satk: 3,
            sdef: 3,
            spe: 3,
            primaryTypeCode: 'notarealtype',
            secondaryTypeCode: null,
            isActive: true,
        );
        $importer->import([$bad], false, false);
    }

    public function testDisableMissingDeactivatesPokemonNotInPayload(): void
    {
        $importer = static::getContainer()->get(PokemonReferenceImporter::class);
        $k1 = 'integration-dm-a-'.bin2hex(random_bytes(4));
        $k2 = 'integration-dm-b-'.bin2hex(random_bytes(4));
        $importer->import([$this->sample($k1), $this->sample($k2)], false, false);

        $r = $importer->import([$this->sample($k1)], true, false);
        self::assertGreaterThanOrEqual(1, $r['disabled']);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $p2 = $em->getRepository(Pokemon::class)->findOneBy(['sourceKey' => $k2]);
        self::assertNotNull($p2);
        self::assertFalse($p2->isActive());
    }

    public function testDryRunDoesNotWriteTypeOrStatsChanges(): void
    {
        $importer = static::getContainer()->get(PokemonReferenceImporter::class);
        $key = 'integration-dry-update-'.bin2hex(random_bytes(4));
        $importer->import([$this->sample($key, 'Original', 'fire', null)], false, false);

        $result = $importer->import([
            $this->sampleWithStats($key, 6, 5, 4, 3, 2, 1, 'Original', 'water', 'flying'),
        ], false, true);

        self::assertTrue($result['dryRunApplied']);
        self::assertSame(0, $result['created']);
        self::assertSame(1, $result['updated']);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $entity = $em->getRepository(Pokemon::class)->findOneBy(['sourceKey' => $key]);
        self::assertNotNull($entity);
        self::assertSame('fire', $entity->getPrimaryType()->getCode());
        self::assertNull($entity->getSecondaryType());
        self::assertSame(1, $entity->getHp());
        self::assertSame(1, $entity->getAtk());
        self::assertSame(1, $entity->getDef());
        self::assertSame(1, $entity->getSatk());
        self::assertSame(1, $entity->getSdef());
        self::assertSame(1, $entity->getSpe());
    }
}

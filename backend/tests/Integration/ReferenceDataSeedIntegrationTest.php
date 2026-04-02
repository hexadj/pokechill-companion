<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Type;
use App\Entity\TypeEffectiveness;
use App\ReferenceData\Seeder\TypeEffectivenessSeeder;
use App\ReferenceData\Seeder\TypeSeeder;
use Doctrine\ORM\EntityManagerInterface;

final class ReferenceDataSeedIntegrationTest extends IntegrationTestCase
{
    public function testSeededTypesCountIsEighteen(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $count = $em->createQuery(
            'SELECT COUNT(t.id) FROM '.Type::class.' t',
        )->getSingleScalarResult();

        self::assertSame(18, (int) $count);
    }

    public function testSeededTypeEffectivenessRowCount(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $count = $em->createQuery(
            'SELECT COUNT(te.id) FROM '.TypeEffectiveness::class.' te',
        )->getSingleScalarResult();

        self::assertSame(18 * 18, (int) $count);
    }

    public function testSecondSeedRunIsIdempotentForCounts(): void
    {
        $container = static::getContainer();
        $container->get(TypeSeeder::class)->seed();
        $container->get(TypeEffectivenessSeeder::class)->seed();

        $em = $container->get(EntityManagerInterface::class);
        $types = (int) $em->createQuery('SELECT COUNT(t.id) FROM '.Type::class.' t')->getSingleScalarResult();
        $matrix = (int) $em->createQuery('SELECT COUNT(te.id) FROM '.TypeEffectiveness::class.' te')->getSingleScalarResult();

        self::assertSame(18, $types);
        self::assertSame(324, $matrix);
    }
}

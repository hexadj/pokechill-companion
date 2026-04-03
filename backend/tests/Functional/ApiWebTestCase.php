<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Pokemon;
use App\Entity\Type;
use App\ReferenceData\Seeder\TypeEffectivenessSeeder;
use App\ReferenceData\Seeder\TypeSeeder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        try {
            $em = static::getContainer()->get(EntityManagerInterface::class);
            $em->getConnection()->executeQuery('SELECT 1');
        } catch (\Throwable $e) {
            self::markTestSkipped('Database unavailable: '.$e->getMessage());
        }

        $container = static::getContainer();
        $container->get(TypeSeeder::class)->seed();
        $container->get(TypeEffectivenessSeeder::class)->seed();

        $this->loadFunctionalPokemonFixtures($em);
    }

    private function loadFunctionalPokemonFixtures(EntityManagerInterface $em): void
    {
        $fire = $em->getRepository(Type::class)->findOneBy(['code' => 'fire']);
        $water = $em->getRepository(Type::class)->findOneBy(['code' => 'water']);
        self::assertNotNull($fire);
        self::assertNotNull($water);

        $rows = [
            ['key' => 'func-list-z', 'name' => 'Zzz Func', 'primary' => $fire, 'secondary' => null, 'active' => true, 'isObtainable' => true, 'obtainabilityCode' => null],
            ['key' => 'func-list-a', 'name' => 'Aaa Func', 'primary' => $water, 'secondary' => null, 'active' => true, 'isObtainable' => true, 'obtainabilityCode' => null],
            ['key' => 'func-list-unob', 'name' => 'Func Unobtainable', 'primary' => $water, 'secondary' => null, 'active' => true, 'isObtainable' => false, 'obtainabilityCode' => 'unobtainable'],
            ['key' => 'func-list-inactive', 'name' => 'Hidden Func', 'primary' => $fire, 'secondary' => null, 'active' => false, 'isObtainable' => true, 'obtainabilityCode' => null],
            ['key' => 'func-opp-b', 'name' => 'Opp B', 'primary' => $water, 'secondary' => null, 'active' => true, 'isObtainable' => true, 'obtainabilityCode' => null],
            ['key' => 'func-opp-a', 'name' => 'Opp A', 'primary' => $fire, 'secondary' => null, 'active' => true, 'isObtainable' => true, 'obtainabilityCode' => null],
            ['key' => 'func-opp-unob', 'name' => 'Opp Unobtainable', 'primary' => $water, 'secondary' => null, 'active' => true, 'isObtainable' => false, 'obtainabilityCode' => 'unobtainable'],
            ['key' => 'func-cand-x', 'name' => 'Cand X', 'primary' => $fire, 'secondary' => null, 'active' => true, 'isObtainable' => true, 'obtainabilityCode' => null],
            ['key' => 'func-cand-unob-best', 'name' => 'Cand Unob Best', 'primary' => $fire, 'secondary' => null, 'active' => true, 'isObtainable' => false, 'obtainabilityCode' => 'unobtainable'],
        ];

        foreach ($rows as $row) {
            $p = $em->getRepository(Pokemon::class)->findOneBy(['sourceKey' => $row['key']]) ?? new Pokemon();
            if ($p->getId() === null) {
                $p->setSourceKey($row['key']);
                $em->persist($p);
            }
            $p->setName($row['name']);
            $p->setPrimaryType($row['primary']);
            $p->setSecondaryType($row['secondary']);
            // Pokechill star ratings 1..6 (must satisfy DB CHECK constraints).
            if ($row['key'] === 'func-cand-unob-best') {
                $p->setHp(6);
                $p->setAtk(6);
                $p->setDef(6);
                $p->setSatk(6);
                $p->setSdef(6);
                $p->setSpe(6);
            } else {
                $p->setHp(3);
                $p->setAtk(4);
                $p->setDef(3);
                $p->setSatk(4);
                $p->setSdef(3);
                $p->setSpe(3);
            }
            $p->setIsActive($row['active']);
            $p->setIsObtainable($row['isObtainable']);
            $p->setObtainabilityCode($row['obtainabilityCode']);
        }

        $em->flush();
    }

    /**
     * @return array<string, mixed>
     */
    protected function assertProblemJsonResponse(int $status, string $type, string $title): array
    {
        self::assertResponseStatusCodeSame($status);

        $contentType = (string) $this->client->getResponse()->headers->get('Content-Type', '');
        self::assertStringStartsWith('application/problem+json', $contentType);

        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($type, $data['type']);
        self::assertSame($title, $data['title']);
        self::assertSame($status, $data['status']);
        self::assertArrayHasKey('detail', $data);

        return $data;
    }
}

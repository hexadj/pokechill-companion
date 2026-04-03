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
            ['func-list-z', 'Zzz Func', $fire, null, true],
            ['func-list-a', 'Aaa Func', $water, null, true],
            ['func-list-inactive', 'Hidden Func', $fire, null, false],
            ['func-opp-b', 'Opp B', $water, null, true],
            ['func-opp-a', 'Opp A', $fire, null, true],
            ['func-cand-x', 'Cand X', $fire, null, true],
        ];

        foreach ($rows as [$key, $name, $primary, $secondary, $active]) {
            if ($em->getRepository(Pokemon::class)->findOneBy(['sourceKey' => $key]) !== null) {
                continue;
            }
            $p = new Pokemon();
            $p->setSourceKey($key);
            $p->setName($name);
            $p->setPrimaryType($primary);
            $p->setSecondaryType($secondary);
            // Pokechill star ratings 1..6 (must satisfy DB CHECK constraints).
            $p->setHp(3);
            $p->setAtk(4);
            $p->setDef(3);
            $p->setSatk(4);
            $p->setSdef(3);
            $p->setSpe(3);
            $p->setIsActive($active);
            $em->persist($p);
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

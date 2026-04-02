<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\ReferenceData\Seeder\TypeEffectivenessSeeder;
use App\ReferenceData\Seeder\TypeSeeder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class IntegrationTestCase extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        try {
            $em = static::getContainer()->get(EntityManagerInterface::class);
            $em->getConnection()->executeQuery('SELECT 1');
        } catch (\Throwable $e) {
            self::markTestSkipped('Database unavailable: '.$e->getMessage());
        }

        $container = static::getContainer();
        $container->get(TypeSeeder::class)->seed();
        $container->get(TypeEffectivenessSeeder::class)->seed();
    }
}

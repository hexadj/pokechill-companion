<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\ReferenceData\Seeder\TypeEffectivenessMatrixBuilder;
use App\Repository\TypeEffectivenessRepository;

final class TypeEffectivenessRepositoryIntegrationTest extends IntegrationTestCase
{
    public function testGetMatrixMatchesBuilderForSampleCells(): void
    {
        $repo = static::getContainer()->get(TypeEffectivenessRepository::class);
        $fromDb = $repo->getMatrix();
        $expected = TypeEffectivenessMatrixBuilder::build();

        foreach (['fire', 'water', 'ghost', 'normal'] as $atk) {
            foreach (['grass', 'steel', 'flying'] as $def) {
                self::assertSame(
                    $expected->getMultiplierX100($atk, $def),
                    $fromDb->getMultiplierX100($atk, $def),
                    sprintf('%s vs %s', $atk, $def),
                );
            }
        }
    }
}

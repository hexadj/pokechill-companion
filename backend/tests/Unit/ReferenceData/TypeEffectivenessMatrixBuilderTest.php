<?php

declare(strict_types=1);

namespace App\Tests\Unit\ReferenceData;

use App\ReferenceData\Seeder\TypeEffectivenessMatrixBuilder;
use PHPUnit\Framework\TestCase;

final class TypeEffectivenessMatrixBuilderTest extends TestCase
{
    public function testMatrixHasEighteenTypes(): void
    {
        $codes = TypeEffectivenessMatrixBuilder::getTypeCodes();
        self::assertCount(18, $codes);
    }

    public function testBuildProducesThreeHundredTwentyFourCells(): void
    {
        $matrix = TypeEffectivenessMatrixBuilder::build();
        $rows = $matrix->toRows();
        self::assertCount(18 * 18, $rows);
    }

    public function testKeyMultipliers(): void
    {
        $m = TypeEffectivenessMatrixBuilder::build();

        self::assertSame(0, $m->getMultiplierX100('normal', 'ghost'));
        self::assertSame(50, $m->getMultiplierX100('fire', 'water'));
        self::assertSame(100, $m->getMultiplierX100('normal', 'electric'));
        self::assertSame(150, $m->getMultiplierX100('water', 'fire'));
    }

    public function testDoubleWeaknessProduct(): void
    {
        $m = TypeEffectivenessMatrixBuilder::build();
        $m1 = $m->getMultiplierX100('fire', 'grass');
        $m2 = $m->getMultiplierX100('fire', 'ice');
        self::assertSame(150, $m1);
        self::assertSame(150, $m2);
        self::assertSame(225, intdiv($m1 * $m2, 100));
    }

    public function testDoubleResistanceProduct(): void
    {
        $m = TypeEffectivenessMatrixBuilder::build();
        $m1 = $m->getMultiplierX100('fire', 'fire');
        $m2 = $m->getMultiplierX100('fire', 'water');
        self::assertSame(50, $m1);
        self::assertSame(50, $m2);
        self::assertSame(25, intdiv($m1 * $m2, 100));
    }
}

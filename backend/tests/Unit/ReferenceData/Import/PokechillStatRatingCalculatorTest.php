<?php

declare(strict_types=1);

namespace App\Tests\Unit\ReferenceData\Import;

use App\ReferenceData\Import\PokechillStatRatingCalculator;
use PHPUnit\Framework\TestCase;

final class PokechillStatRatingCalculatorTest extends TestCase
{
    /**
     * @return iterable<string, array{int, int}>
     */
    public static function referenceCasesProvider(): iterable
    {
        yield '20' => [20, 1];
        yield '35' => [35, 1];
        yield '45' => [45, 2];
        yield '55' => [55, 2];
        yield '95' => [95, 3];
        yield '110' => [110, 4];
        yield '150' => [150, 5];
        yield '182' => [182, 6];
        yield '200' => [200, 6];
        yield '0' => [0, 1];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('referenceCasesProvider')]
    public function testBaseStatToStarRatingMatchesPokechillReference(int $baseStat, int $expectedStar): void
    {
        $calc = new PokechillStatRatingCalculator();
        self::assertSame($expectedStar, $calc->baseStatToStarRating($baseStat));
    }

    public function testOutputIsAlwaysBetweenOneAndSix(): void
    {
        $calc = new PokechillStatRatingCalculator();
        for ($i = -1000; $i <= 1000; ++$i) {
            $s = $calc->baseStatToStarRating($i);
            self::assertGreaterThanOrEqual(1, $s);
            self::assertLessThanOrEqual(6, $s);
        }
    }
}

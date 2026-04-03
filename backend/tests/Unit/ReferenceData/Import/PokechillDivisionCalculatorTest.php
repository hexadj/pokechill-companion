<?php

declare(strict_types=1);

namespace App\Tests\Unit\ReferenceData\Import;

use App\ReferenceData\Import\PokechillDivisionCalculator;
use PHPUnit\Framework\TestCase;

final class PokechillDivisionCalculatorTest extends TestCase
{
    public function testBstSumAddsSixStats(): void
    {
        $calc = new PokechillDivisionCalculator();
        self::assertSame(21, $calc->bstSum(1, 2, 3, 4, 5, 6));
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function divisionReferenceCasesProvider(): iterable
    {
        yield '9 D' => [9, 'D'];
        yield '10 C' => [10, 'C'];
        yield '13 C' => [13, 'C'];
        yield '14 B' => [14, 'B'];
        yield '15 B' => [15, 'B'];
        yield '16 A' => [16, 'A'];
        yield '18 A' => [18, 'A'];
        yield '19 S' => [19, 'S'];
        yield '20 S' => [20, 'S'];
        yield '21 SS' => [21, 'SS'];
        yield '23 SS' => [23, 'SS'];
        yield '24 SSS' => [24, 'SSS'];
        yield '36 SSS' => [36, 'SSS'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('divisionReferenceCasesProvider')]
    public function testDivisionFromBstSumMatchesReference(int $sum, string $expected): void
    {
        $calc = new PokechillDivisionCalculator();
        self::assertSame($expected, $calc->divisionFromBstSum($sum));
    }
}

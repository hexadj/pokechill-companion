<?php

declare(strict_types=1);

namespace App\ReferenceData\Import;

/**
 * Pokechill division tier from the sum of six star ratings (1..6 each).
 *
 * @see project-management/conception/10-pokechill-stars-and-division.md
 */
final class PokechillDivisionCalculator
{
    /**
     * Canonical order for validation and UI; matches {@see divisionFromBstSum()} outputs.
     *
     * @var list<'D'|'C'|'B'|'A'|'S'|'SS'|'SSS'>
     */
    public const DIVISION_CODES = ['D', 'C', 'B', 'A', 'S', 'SS', 'SSS'];

    public function bstSum(int $hp, int $atk, int $def, int $satk, int $sdef, int $spe): int
    {
        return $hp + $atk + $def + $satk + $sdef + $spe;
    }

    /**
     * @return 'D'|'C'|'B'|'A'|'S'|'SS'|'SSS'
     */
    public function divisionFromBstSum(int $totalBstSum): string
    {
        if ($totalBstSum < 10) {
            return 'D';
        }
        if ($totalBstSum < 14) {
            return 'C';
        }
        if ($totalBstSum < 16) {
            return 'B';
        }
        if ($totalBstSum < 19) {
            return 'A';
        }
        if ($totalBstSum < 21) {
            return 'S';
        }
        if ($totalBstSum < 24) {
            return 'SS';
        }

        return 'SSS';
    }
}

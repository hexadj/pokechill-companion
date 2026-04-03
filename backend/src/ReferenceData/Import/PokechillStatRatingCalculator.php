<?php

declare(strict_types=1);

namespace App\ReferenceData\Import;

/**
 * Reproduces upstream Pokechill statToRating: raw BST to star rating 1..6.
 *
 * @see project-management/conception/10-pokechill-stars-and-division.md
 */
final class PokechillStatRatingCalculator
{
    /**
     * Equivalent to: clamp(round(1 + (baseStat - 20) / 36), 1, 6).
     */
    public function baseStatToStarRating(int $baseStat): int
    {
        $rating = (int) round(($baseStat + 16) / 36);

        return max(1, min(6, $rating));
    }
}

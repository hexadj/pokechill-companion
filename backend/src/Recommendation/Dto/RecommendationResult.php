<?php

declare(strict_types=1);

namespace App\Recommendation\Dto;

/**
 * Output contract for the V1 recommendation use-case.
 */
final class RecommendationResult
{
    /**
     * @param OpponentPokemonView[] $opponentTeam
     * @param RecommendationView[] $recommendations
     */
    public function __construct(
        public readonly array $opponentTeam,
        public readonly array $recommendations,
    ) {
    }
}


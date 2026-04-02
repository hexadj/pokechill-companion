<?php

declare(strict_types=1);

namespace App\Recommendation\Service;

use App\Recommendation\Dto\RecommendationQuery;
use App\Recommendation\Dto\RecommendationResult;
use App\Repository\PokemonRepository;
use App\Repository\TypeEffectivenessRepository;

/**
 * Orchestrates the V1 recommendation use-case.
 *
 * Note: this is intentionally a skeleton to unblock the next steps
 * (API wiring and full orchestration logic).
 */
final class RecommendationService
{
    public function __construct(
        private readonly PokemonRepository $pokemonRepository,
        private readonly TypeEffectivenessRepository $typeEffectivenessRepository,
        private readonly MatchupScorer $matchupScorer,
    ) {
    }

    public function recommend(RecommendationQuery $query): RecommendationResult
    {
        // Placeholder: full V1 orchestration will be implemented in the next phase.
        // Returning empty, structurally valid results for now.
        return new RecommendationResult(
            opponentTeam: [],
            recommendations: [],
        );
    }
}


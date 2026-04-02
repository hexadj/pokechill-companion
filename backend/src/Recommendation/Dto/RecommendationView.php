<?php

declare(strict_types=1);

namespace App\Recommendation\Dto;

/**
 * Public view of a recommendation result (V1 contract).
 */
final class RecommendationView
{
    /**
     * @param MatchupView[] $matchups
     */
    public function __construct(
        public readonly string $sourceKey,
        public readonly string $name,
        public readonly string $primaryTypeCode,
        public readonly ?string $secondaryTypeCode,
        public readonly float $score,
        public readonly array $matchups,
    ) {
    }
}


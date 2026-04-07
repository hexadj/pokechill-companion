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
        public readonly int $hp,
        public readonly int $atk,
        public readonly int $def,
        public readonly int $satk,
        public readonly int $sdef,
        public readonly int $spe,
        public readonly string $division,
        public readonly float $score,
        public readonly array $matchups,
    ) {
    }
}


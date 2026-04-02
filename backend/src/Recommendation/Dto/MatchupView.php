<?php

declare(strict_types=1);

namespace App\Recommendation\Dto;

/**
 * Output of the pure matchup scoring (V1 contract).
 */
final class MatchupView
{
    public function __construct(
        public readonly string $opponentSourceKey,
        public readonly string $bestAttackTypeCode,
        public readonly string $bestAttackCategory, // "physical" | "special"
        public readonly int $typeMultiplierX100,
        public readonly float $physicalScore,
        public readonly float $specialScore,
        public readonly float $selectedScore,
    ) {
    }
}


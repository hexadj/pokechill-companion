<?php

declare(strict_types=1);

namespace App\Recommendation\Dto;

/**
 * Input contract for the V1 recommendation use-case.
 */
final class RecommendationQuery
{
    /**
     * @param string[] $opponentSourceKeys
     */
    public function __construct(
        public readonly array $opponentSourceKeys,
        public readonly int $limit,
    ) {
    }
}


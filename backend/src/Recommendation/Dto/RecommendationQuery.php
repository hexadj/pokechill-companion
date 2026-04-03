<?php

declare(strict_types=1);

namespace App\Recommendation\Dto;

/**
 * Input contract for the V1 recommendation use-case.
 */
final class RecommendationQuery
{
    /**
     * @param string[]          $opponentSourceKeys
     * @param list<string>|null $divisionCodes      null = all divisions; non-null = filter candidates to these codes
     */
    public function __construct(
        public readonly array $opponentSourceKeys,
        public readonly int $limit,
        public readonly bool $includeNonObtainable = false,
        public readonly ?array $divisionCodes = null,
    ) {
    }
}


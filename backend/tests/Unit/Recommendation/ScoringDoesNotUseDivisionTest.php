<?php

declare(strict_types=1);

namespace App\Tests\Unit\Recommendation;

use PHPUnit\Framework\TestCase;

/**
 * Guardrail: division tiers are display metadata; V1 scoring must not depend on them.
 */
final class ScoringDoesNotUseDivisionTest extends TestCase
{
    public function testMatchupScorerDoesNotReferenceDivisionCalculator(): void
    {
        $path = dirname(__DIR__, 3).'/src/Recommendation/Service/MatchupScorer.php';
        $src = (string) file_get_contents($path);
        self::assertStringNotContainsString('PokechillDivisionCalculator', $src);
        self::assertStringNotContainsString('divisionFromBstSum', $src);
    }
}

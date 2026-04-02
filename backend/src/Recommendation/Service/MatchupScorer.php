<?php

declare(strict_types=1);

namespace App\Recommendation\Service;

use App\Entity\Pokemon;
use App\Recommendation\Dto\MatchupView;
use App\ReferenceData\Dto\TypeEffectivenessMatrix;

/**
 * Pure (no IO) matchup scoring service for V1.
 *
 * Determinism:
 * - offensive type iteration order is primary then secondary
 * - ties are handled with an epsilon tolerance
 */
final class MatchupScorer
{
    /**
     * Small tolerance for float comparisons.
     * Chosen to be large enough to absorb division artifacts, but still strict.
     */
    private const EPS = 1e-9;

    public function scoreMatchup(Pokemon $candidate, Pokemon $opponent, TypeEffectivenessMatrix $matrix): MatchupView
    {
        $opponentSourceKey = $opponent->getSourceKey();

        $offensiveTypes = [];
        $offensiveTypes[] = strtolower($candidate->getPrimaryType()->getCode());
        $secondaryType = $candidate->getSecondaryType();
        if ($secondaryType !== null) {
            $offensiveTypes[] = strtolower($secondaryType->getCode());
        }

        $bestSelectedScore = -INF;
        $bestAttackTypeCode = '';
        $bestAttackCategory = '';
        $bestTypeMultiplierX100 = 0;
        $bestPhysicalScore = 0.0;
        $bestSpecialScore = 0.0;

        foreach ($offensiveTypes as $attackTypeCode) {
            $primaryDefendingTypeCode = $opponent->getPrimaryType()->getCode();
            $m1 = $matrix->getMultiplierX100($attackTypeCode, $primaryDefendingTypeCode);

            $typeMultiplierX100 = $m1;
            $secondaryOpponentType = $opponent->getSecondaryType();
            if ($secondaryOpponentType !== null) {
                $secondaryDefendingTypeCode = $secondaryOpponentType->getCode();
                $m2 = $matrix->getMultiplierX100($attackTypeCode, $secondaryDefendingTypeCode);
                $typeMultiplierX100 = \intdiv($m1 * $m2, 100);
            }

            $multiplier = $typeMultiplierX100 / 100.0;

            $physicalScore = $multiplier * ($candidate->getAtk() / $opponent->getDef());
            $specialScore = $multiplier * ($candidate->getSatk() / $opponent->getSdef());

            // Category selection: deterministic physical preference on (near) ties.
            if ($physicalScore + self::EPS >= $specialScore) {
                $selectedScore = $physicalScore;
                $attackCategory = 'physical';
            } else {
                $selectedScore = $specialScore;
                $attackCategory = 'special';
            }

            if ($selectedScore > $bestSelectedScore + self::EPS) {
                $bestSelectedScore = $selectedScore;
                $bestAttackTypeCode = $attackTypeCode;
                $bestAttackCategory = $attackCategory;
                $bestTypeMultiplierX100 = $typeMultiplierX100;
                $bestPhysicalScore = $physicalScore;
                $bestSpecialScore = $specialScore;
            }
        }

        return new MatchupView(
            opponentSourceKey: $opponentSourceKey,
            bestAttackTypeCode: $bestAttackTypeCode,
            bestAttackCategory: $bestAttackCategory,
            typeMultiplierX100: $bestTypeMultiplierX100,
            physicalScore: $bestPhysicalScore,
            specialScore: $bestSpecialScore,
            selectedScore: $bestSelectedScore,
        );
    }
}


<?php

declare(strict_types=1);

namespace App\Recommendation\Service;

use App\Entity\Pokemon;
use App\Recommendation\Dto\OpponentPokemonView;
use App\Recommendation\Dto\RecommendationQuery;
use App\Recommendation\Dto\RecommendationResult;
use App\Recommendation\Dto\RecommendationView;
use App\Recommendation\Exception\InvalidRecommendationQueryException;
use App\Recommendation\Exception\OpponentPokemonNotFoundException;
use App\ReferenceData\Import\PokechillDivisionCalculator;
use App\Repository\PokemonRepository;
use App\Repository\TypeEffectivenessRepository;

/**
 * Orchestrates the V1 recommendation use-case.
 */
final class RecommendationService
{
    private const EPS = 1e-9;

    public function __construct(
        private readonly PokemonRepository $pokemonRepository,
        private readonly TypeEffectivenessRepository $typeEffectivenessRepository,
        private readonly MatchupScorer $matchupScorer,
        private readonly PokechillDivisionCalculator $pokechillDivisionCalculator,
    ) {
    }

    public function recommend(RecommendationQuery $query): RecommendationResult
    {
        $this->validateQuery($query);

        $opponentSourceKeys = $query->opponentSourceKeys;
        $uniqueKeys = array_values(array_unique(array_map('strval', $opponentSourceKeys)));

        $byKey = $this->pokemonRepository->findActiveBySourceKeys($uniqueKeys);

        $opponentsOrdered = [];
        $opponentTeam = [];
        $missingSourceKeys = [];
        $seenMissing = [];

        foreach ($opponentSourceKeys as $sourceKey) {
            if (!isset($byKey[$sourceKey])) {
                if (!isset($seenMissing[$sourceKey])) {
                    $missingSourceKeys[] = $sourceKey;
                    $seenMissing[$sourceKey] = true;
                }
                continue;
            }

            $pokemon = $byKey[$sourceKey];
            $opponentsOrdered[] = $pokemon;
            $opponentTeam[] = $this->toOpponentView($pokemon);
        }

        if ($missingSourceKeys !== []) {
            throw OpponentPokemonNotFoundException::forSourceKeys($missingSourceKeys);
        }

        $matrix = $this->typeEffectivenessRepository->getMatrix();
        $candidates = $this->pokemonRepository->findAllActive();
        $candidates = $this->filterCandidates($query, $candidates);

        $rows = [];
        foreach ($candidates as $candidate) {
            $matchups = [];
            $teamScore = 0.0;
            $maxSelectedScore = -INF;

            foreach ($opponentsOrdered as $opponent) {
                $mv = $this->matchupScorer->scoreMatchup($candidate, $opponent, $matrix);
                $matchups[] = $mv;
                $teamScore += $mv->selectedScore;
                if ($mv->selectedScore > $maxSelectedScore) {
                    $maxSelectedScore = $mv->selectedScore;
                }
            }

            $rows[] = [
                'candidate' => $candidate,
                'teamScore' => $teamScore,
                'maxSelectedScore' => $maxSelectedScore,
                'matchups' => $matchups,
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            $cmp = self::compareFloatDesc($a['teamScore'], $b['teamScore']);
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = self::compareFloatDesc($a['maxSelectedScore'], $b['maxSelectedScore']);
            if ($cmp !== 0) {
                return $cmp;
            }

            $aMaxOff = max($a['candidate']->getAtk(), $a['candidate']->getSatk());
            $bMaxOff = max($b['candidate']->getAtk(), $b['candidate']->getSatk());
            $cmp = $bMaxOff <=> $aMaxOff;
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp($a['candidate']->getSourceKey(), $b['candidate']->getSourceKey());
        });

        $limit = min($query->limit, \count($rows));
        $recommendations = [];
        for ($i = 0; $i < $limit; ++$i) {
            $row = $rows[$i];
            $candidate = $row['candidate'];
            $recommendations[] = new RecommendationView(
                sourceKey: $candidate->getSourceKey(),
                name: $candidate->getName(),
                primaryTypeCode: strtolower($candidate->getPrimaryType()->getCode()),
                secondaryTypeCode: $candidate->getSecondaryType() !== null
                    ? strtolower($candidate->getSecondaryType()->getCode())
                    : null,
                score: $row['teamScore'],
                matchups: $row['matchups'],
            );
        }

        return new RecommendationResult(
            opponentTeam: $opponentTeam,
            recommendations: $recommendations,
        );
    }

    /**
     * @param Pokemon[] $candidates
     *
     * @return Pokemon[]
     */
    private function filterCandidates(RecommendationQuery $query, array $candidates): array
    {
        $allowedDivisions = null;
        if ($query->divisionCodes !== null) {
            $allowedDivisions = array_flip($query->divisionCodes);
        }

        $out = [];
        foreach ($candidates as $candidate) {
            if (!$query->includeNonObtainable && !$candidate->isObtainable()) {
                continue;
            }
            if ($allowedDivisions !== null) {
                $bst = $this->pokechillDivisionCalculator->bstSum(
                    $candidate->getHp(),
                    $candidate->getAtk(),
                    $candidate->getDef(),
                    $candidate->getSatk(),
                    $candidate->getSdef(),
                    $candidate->getSpe(),
                );
                $division = $this->pokechillDivisionCalculator->divisionFromBstSum($bst);
                if (!isset($allowedDivisions[$division])) {
                    continue;
                }
            }
            $out[] = $candidate;
        }

        return $out;
    }

    private function validateQuery(RecommendationQuery $query): void
    {
        if ($query->limit < 1) {
            throw new InvalidRecommendationQueryException('limit must be a positive integer.');
        }

        $keys = $query->opponentSourceKeys;
        $count = \count($keys);
        if ($count < 1 || $count > 6) {
            throw new InvalidRecommendationQueryException('opponentSourceKeys must contain between 1 and 6 entries.');
        }

        foreach ($keys as $key) {
            if (!\is_string($key) || trim($key) === '') {
                throw new InvalidRecommendationQueryException('Each opponent source key must be a non-empty string.');
            }
        }
    }

    private function toOpponentView(Pokemon $pokemon): OpponentPokemonView
    {
        $hp = $pokemon->getHp();
        $atk = $pokemon->getAtk();
        $def = $pokemon->getDef();
        $satk = $pokemon->getSatk();
        $sdef = $pokemon->getSdef();
        $spe = $pokemon->getSpe();
        $bstSum = $this->pokechillDivisionCalculator->bstSum($hp, $atk, $def, $satk, $sdef, $spe);

        return new OpponentPokemonView(
            sourceKey: $pokemon->getSourceKey(),
            name: $pokemon->getName(),
            primaryTypeCode: strtolower($pokemon->getPrimaryType()->getCode()),
            secondaryTypeCode: $pokemon->getSecondaryType() !== null
                ? strtolower($pokemon->getSecondaryType()->getCode())
                : null,
            hp: $hp,
            atk: $atk,
            def: $def,
            satk: $satk,
            sdef: $sdef,
            spe: $spe,
            bstSum: $bstSum,
            division: $this->pokechillDivisionCalculator->divisionFromBstSum($bstSum),
            isObtainable: $pokemon->isObtainable(),
            obtainabilityCode: $pokemon->getObtainabilityCode(),
        );
    }

    private static function compareFloatDesc(float $left, float $right): int
    {
        if (abs($left - $right) < self::EPS) {
            return 0;
        }

        return $left > $right ? -1 : 1;
    }
}

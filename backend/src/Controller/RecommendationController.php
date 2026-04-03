<?php

declare(strict_types=1);

namespace App\Controller;

use App\Error\ApiRequestValidationException;
use App\Error\InvalidJsonBodyException;
use App\Recommendation\Dto\MatchupView;
use App\Recommendation\Dto\OpponentPokemonView;
use App\Recommendation\Dto\RecommendationQuery;
use App\Recommendation\Dto\RecommendationResult;
use App\Recommendation\Dto\RecommendationView;
use App\Recommendation\Service\RecommendationService;
use App\ReferenceData\Import\PokechillDivisionCalculator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
final class RecommendationController
{
    private const ALLOWED_KEYS = ['opponentSourceKeys', 'limit', 'includeNonObtainable', 'divisionCodes'];

    public function __construct(
        private readonly RecommendationService $recommendationService,
    ) {
    }

    #[Route('/recommendations', name: 'api_v1_recommendations_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $content = $request->getContent();
        if ($content === '') {
            throw new InvalidJsonBodyException('Invalid JSON payload.');
        }

        try {
            $decoded = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidJsonBodyException('Invalid JSON payload.', 0, $e);
        }

        if (\is_array($decoded)) {
            throw new InvalidJsonBodyException('Request body must be a JSON object.');
        }

        if (!\is_object($decoded)) {
            throw new InvalidJsonBodyException('Request body must be a JSON object.');
        }

        try {
            $reEncoded = json_encode($decoded, JSON_THROW_ON_ERROR);
            /** @var array<string, mixed> $data */
            $data = json_decode($reEncoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidJsonBodyException('Invalid JSON payload.', 0, $e);
        }

        $this->assertNoUnknownKeys($data);

        if (!\array_key_exists('opponentSourceKeys', $data)) {
            throw ApiRequestValidationException::recommendationPayloadInvalid(
                'Validation failed.',
                ['opponentSourceKeys' => ['opponentSourceKeys is required.']],
            );
        }

        $opponentKeys = $this->normalizeOpponentSourceKeys($data['opponentSourceKeys']);
        $limit = $this->parseRecommendationLimit($data);
        $includeNonObtainable = $this->parseIncludeNonObtainable($data);
        $divisionCodes = $this->parseDivisionCodes($data);

        $query = new RecommendationQuery($opponentKeys, $limit, $includeNonObtainable, $divisionCodes);
        $result = $this->recommendationService->recommend($query);

        return new JsonResponse($this->resultToArray($result));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertNoUnknownKeys(array $data): void
    {
        $unknown = array_diff(array_keys($data), self::ALLOWED_KEYS);
        if ($unknown !== []) {
            throw ApiRequestValidationException::unknownJsonKeys(array_values($unknown));
        }
    }

    /**
     * @param mixed $raw
     *
     * @return string[]
     */
    private function normalizeOpponentSourceKeys(mixed $raw): array
    {
        if (!\is_array($raw)) {
            throw ApiRequestValidationException::recommendationPayloadInvalid(
                'Validation failed.',
                ['opponentSourceKeys' => ['opponentSourceKeys must be an array.']],
            );
        }

        $out = [];
        foreach ($raw as $idx => $item) {
            if (!\is_string($item)) {
                throw ApiRequestValidationException::recommendationPayloadInvalid(
                    'Validation failed.',
                    ['opponentSourceKeys' => [sprintf('Entry at index %s must be a non-empty string.', $idx)]],
                );
            }
            $trimmed = trim($item);
            if ($trimmed === '') {
                throw ApiRequestValidationException::recommendationPayloadInvalid(
                    'Validation failed.',
                    ['opponentSourceKeys' => [sprintf('Entry at index %s must be a non-empty string.', $idx)]],
                );
            }
            $out[] = $trimmed;
        }

        $count = \count($out);
        if ($count < 1 || $count > 6) {
            throw ApiRequestValidationException::recommendationPayloadInvalid(
                'Validation failed.',
                ['opponentSourceKeys' => ['opponentSourceKeys must contain between 1 and 6 entries.']],
            );
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function parseRecommendationLimit(array $data): int
    {
        if (!\array_key_exists('limit', $data)) {
            return 20;
        }

        $limit = $this->coercePositiveInt($data['limit']);
        if ($limit === null || $limit < 1 || $limit > 50) {
            throw ApiRequestValidationException::recommendationPayloadInvalid(
                'Validation failed.',
                ['limit' => ['limit must be an integer between 1 and 50.']],
            );
        }

        return $limit;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function parseIncludeNonObtainable(array $data): bool
    {
        if (!\array_key_exists('includeNonObtainable', $data)) {
            return false;
        }

        $raw = $data['includeNonObtainable'];
        if (!\is_bool($raw)) {
            throw ApiRequestValidationException::recommendationPayloadInvalid(
                'Validation failed.',
                ['includeNonObtainable' => ['includeNonObtainable must be a boolean.']],
            );
        }

        return $raw;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<string>|null
     */
    private function parseDivisionCodes(array $data): ?array
    {
        if (!\array_key_exists('divisionCodes', $data)) {
            return null;
        }

        $raw = $data['divisionCodes'];
        if (!\is_array($raw)) {
            throw ApiRequestValidationException::recommendationPayloadInvalid(
                'Validation failed.',
                ['divisionCodes' => ['divisionCodes must be an array.']],
            );
        }

        if ($raw === []) {
            throw ApiRequestValidationException::recommendationPayloadInvalid(
                'Validation failed.',
                ['divisionCodes' => ['divisionCodes must contain at least one division code.']],
            );
        }

        $validSet = array_flip(PokechillDivisionCalculator::DIVISION_CODES);
        $out = [];
        $seen = [];

        foreach ($raw as $idx => $item) {
            if (!\is_string($item)) {
                throw ApiRequestValidationException::recommendationPayloadInvalid(
                    'Validation failed.',
                    ['divisionCodes' => [sprintf('Entry at index %s must be a non-empty string.', $idx)]],
                );
            }
            $trimmed = trim($item);
            if ($trimmed === '') {
                throw ApiRequestValidationException::recommendationPayloadInvalid(
                    'Validation failed.',
                    ['divisionCodes' => [sprintf('Entry at index %s must be a non-empty string.', $idx)]],
                );
            }
            if (!isset($validSet[$trimmed])) {
                throw ApiRequestValidationException::recommendationPayloadInvalid(
                    'Validation failed.',
                    ['divisionCodes' => [sprintf('Unknown division code: %s.', $trimmed)]],
                );
            }
            if (!isset($seen[$trimmed])) {
                $seen[$trimmed] = true;
                $out[] = $trimmed;
            }
        }

        return $out;
    }

    /**
     * JSON numbers may decode as int or float; reject non-whole values.
     */
    private function coercePositiveInt(mixed $raw): ?int
    {
        if (\is_int($raw)) {
            return $raw;
        }

        if (\is_float($raw)) {
            if (!is_finite($raw) || floor($raw) !== $raw) {
                return null;
            }

            return (int) $raw;
        }

        return null;
    }

    /**
     * @return array{opponentTeam: list<array<string, mixed>>, recommendations: list<array<string, mixed>>}
     */
    private function resultToArray(RecommendationResult $result): array
    {
        return [
            'opponentTeam' => array_map(fn (OpponentPokemonView $v): array => $this->opponentToArray($v), $result->opponentTeam),
            'recommendations' => array_map(fn (RecommendationView $v): array => $this->recommendationToArray($v), $result->recommendations),
        ];
    }

    /**
     * @return array{
     *     sourceKey: string,
     *     name: string,
     *     primaryTypeCode: string,
     *     secondaryTypeCode: string|null,
     *     hp: int,
     *     atk: int,
     *     def: int,
     *     satk: int,
     *     sdef: int,
     *     spe: int,
     *     bstSum: int,
     *     division: string,
     *     isObtainable: bool,
     *     obtainabilityCode: string|null
     * }
     */
    private function opponentToArray(OpponentPokemonView $v): array
    {
        return [
            'sourceKey' => $v->sourceKey,
            'name' => $v->name,
            'primaryTypeCode' => $v->primaryTypeCode,
            'secondaryTypeCode' => $v->secondaryTypeCode,
            'hp' => $v->hp,
            'atk' => $v->atk,
            'def' => $v->def,
            'satk' => $v->satk,
            'sdef' => $v->sdef,
            'spe' => $v->spe,
            'bstSum' => $v->bstSum,
            'division' => $v->division,
            'isObtainable' => $v->isObtainable,
            'obtainabilityCode' => $v->obtainabilityCode,
        ];
    }

    /**
     * @return array{
     *     sourceKey: string,
     *     name: string,
     *     primaryTypeCode: string,
     *     secondaryTypeCode: string|null,
     *     score: float,
     *     matchups: list<array<string, mixed>>
     * }
     */
    private function recommendationToArray(RecommendationView $v): array
    {
        return [
            'sourceKey' => $v->sourceKey,
            'name' => $v->name,
            'primaryTypeCode' => $v->primaryTypeCode,
            'secondaryTypeCode' => $v->secondaryTypeCode,
            'score' => $v->score,
            'matchups' => array_map(fn (MatchupView $m): array => $this->matchupToArray($m), $v->matchups),
        ];
    }

    /**
     * @return array{
     *     opponentSourceKey: string,
     *     bestAttackTypeCode: string,
     *     bestAttackCategory: string,
     *     typeMultiplierX100: int,
     *     physicalScore: float,
     *     specialScore: float,
     *     selectedScore: float
     * }
     */
    private function matchupToArray(MatchupView $m): array
    {
        return [
            'opponentSourceKey' => $m->opponentSourceKey,
            'bestAttackTypeCode' => $m->bestAttackTypeCode,
            'bestAttackCategory' => $m->bestAttackCategory,
            'typeMultiplierX100' => $m->typeMultiplierX100,
            'physicalScore' => $m->physicalScore,
            'specialScore' => $m->specialScore,
            'selectedScore' => $m->selectedScore,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Pokemon;
use App\Error\ApiRequestValidationException;
use App\Repository\PokemonRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/reference')]
final class ReferencePokemonController
{
    public function __construct(
        private readonly PokemonRepository $pokemonRepository,
    ) {
    }

    #[Route('/pokemon', name: 'api_v1_reference_pokemon_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $limit = $this->parseLimit($request);
        $search = $this->parseSearch($request);

        $rows = $this->pokemonRepository->findActiveForList($search, $limit);

        return new JsonResponse([
            'items' => array_map(fn (Pokemon $p): array => $this->pokemonToArray($p), $rows),
        ]);
    }

    private function parseLimit(Request $request): int
    {
        $raw = $request->query->get('limit');
        if ($raw === null || $raw === '') {
            return 20;
        }

        if ($raw !== null && !is_scalar($raw)) {
            throw ApiRequestValidationException::invalidLimitQuery();
        }

        $string = (string) $raw;
        if (!ctype_digit($string)) {
            throw ApiRequestValidationException::invalidLimitQuery();
        }

        $int = (int) $string;
        if ($int < 1) {
            throw ApiRequestValidationException::invalidLimitQuery();
        }

        return min($int, 100);
    }

    private function parseSearch(Request $request): ?string
    {
        if (!$request->query->has('search')) {
            return null;
        }

        $value = $request->query->get('search');
        if (!is_string($value)) {
            throw ApiRequestValidationException::invalidSearchQuery();
        }

        return $value;
    }

    /**
     * @return array{sourceKey: string, name: string, primaryTypeCode: string, secondaryTypeCode: string|null}
     */
    private function pokemonToArray(Pokemon $pokemon): array
    {
        return [
            'sourceKey' => $pokemon->getSourceKey(),
            'name' => $pokemon->getName(),
            'primaryTypeCode' => strtolower($pokemon->getPrimaryType()->getCode()),
            'secondaryTypeCode' => $pokemon->getSecondaryType() !== null
                ? strtolower($pokemon->getSecondaryType()->getCode())
                : null,
        ];
    }
}

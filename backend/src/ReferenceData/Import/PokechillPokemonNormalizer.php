<?php

declare(strict_types=1);

namespace App\ReferenceData\Import;

use App\ReferenceData\Dto\PokemonReferenceData;
use App\ReferenceData\Import\Dto\ExtractedPokechillPokemon;
use RuntimeException;

/**
 * Normalizes extracted Pokechill entries into our V1 persistence contract.
 *
 * Raw BST from the extractor is converted with {@see PokechillStatRatingCalculator}.
 */
final class PokechillPokemonNormalizer
{
    public function __construct(
        private readonly PokechillStatRatingCalculator $statRatingCalculator,
    ) {
    }

    /**
     * @param ExtractedPokechillPokemon[] $extracted
     * @return PokemonReferenceData[]
     */
    public function normalize(array $extracted): array
    {
        $result = [];

        foreach ($extracted as $pokemon) {
            if (!$pokemon instanceof ExtractedPokechillPokemon) {
                throw new RuntimeException('Unexpected extracted pokemon payload.');
            }

            $primaryTypeCode = trim($pokemon->primaryTypeCode);
            if ($primaryTypeCode === '') {
                throw new RuntimeException(sprintf('Missing primaryTypeCode for "%s".', $pokemon->sourceKey));
            }

            $secondaryTypeCode = $pokemon->secondaryTypeCode !== null ? trim($pokemon->secondaryTypeCode) : null;
            if ($secondaryTypeCode !== null && $secondaryTypeCode === '') {
                $secondaryTypeCode = null;
            }

            $name = $pokemon->rename !== null
                ? trim($pokemon->rename)
                : $this->formatNameFromSourceKey($pokemon->sourceKey);

            if ($name === '') {
                throw new RuntimeException(sprintf('Missing name for "%s".', $pokemon->sourceKey));
            }

            $result[] = new PokemonReferenceData(
                sourceKey: $pokemon->sourceKey,
                name: $name,
                hp: $this->statRatingCalculator->baseStatToStarRating($pokemon->hp),
                atk: $this->statRatingCalculator->baseStatToStarRating($pokemon->atk),
                def: $this->statRatingCalculator->baseStatToStarRating($pokemon->def),
                satk: $this->statRatingCalculator->baseStatToStarRating($pokemon->satk),
                sdef: $this->statRatingCalculator->baseStatToStarRating($pokemon->sdef),
                spe: $this->statRatingCalculator->baseStatToStarRating($pokemon->spe),
                primaryTypeCode: strtolower($primaryTypeCode),
                secondaryTypeCode: $secondaryTypeCode !== null ? strtolower($secondaryTypeCode) : null,
                isActive: true,
            );
        }

        return $result;
    }

    private function formatNameFromSourceKey(string $sourceKey): string
    {
        // CamelCase / PascalCase => words.
        $withSpaces = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $sourceKey);
        if ($withSpaces === null) {
            $withSpaces = $sourceKey;
        }

        $withSpaces = str_replace(['-', '_'], ' ', $withSpaces);
        $withSpaces = preg_replace('/\\s+/', ' ', trim((string) $withSpaces));

        if ($withSpaces === '') {
            return '';
        }

        $words = explode(' ', $withSpaces);
        $formatted = [];
        foreach ($words as $word) {
            $word = trim($word);
            if ($word === '') {
                continue;
            }

            // Keep punctuation like "." as part of the word.
            $lower = strtolower($word);
            $formatted[] = strtoupper($lower[0]) . substr($lower, 1);
        }

        return implode(' ', $formatted);
    }
}


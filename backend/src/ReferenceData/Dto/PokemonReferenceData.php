<?php

declare(strict_types=1);

namespace App\ReferenceData\Dto;

/**
 * Minimal reference contract for an imported Pokemon (V1).
 *
 * The six stat fields are Pokechill star ratings 1..6 (statToRating), not raw BST.
 */
final class PokemonReferenceData
{
    /**
     * @param int $hp    star rating 1..6
     * @param int $atk   star rating 1..6
     * @param int $def   star rating 1..6
     * @param int $satk  star rating 1..6
     * @param int $sdef  star rating 1..6
     * @param int $spe   star rating 1..6
     */
    public function __construct(
        public readonly string $sourceKey,
        public readonly string $name,
        public readonly int $hp,
        public readonly int $atk,
        public readonly int $def,
        public readonly int $satk,
        public readonly int $sdef,
        public readonly int $spe,
        public readonly string $primaryTypeCode,
        public readonly ?string $secondaryTypeCode,
        public readonly bool $isActive,
    ) {
    }
}


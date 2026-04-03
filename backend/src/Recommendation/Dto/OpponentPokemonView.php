<?php

declare(strict_types=1);

namespace App\Recommendation\Dto;

/**
 * Public view of an opponent Pokemon (V1 contract).
 *
 * Stat fields are Pokechill star ratings 1..6; bstSum and division are informative (see phase 9).
 */
final class OpponentPokemonView
{
    public function __construct(
        public readonly string $sourceKey,
        public readonly string $name,
        public readonly string $primaryTypeCode,
        public readonly ?string $secondaryTypeCode,
        public readonly int $hp,
        public readonly int $atk,
        public readonly int $def,
        public readonly int $satk,
        public readonly int $sdef,
        public readonly int $spe,
        public readonly int $bstSum,
        public readonly string $division,
    ) {
    }
}


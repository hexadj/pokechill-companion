<?php

declare(strict_types=1);

namespace App\Recommendation\Dto;

/**
 * Public view of an opponent Pokemon (V1 contract).
 */
final class OpponentPokemonView
{
    public function __construct(
        public readonly string $sourceKey,
        public readonly string $name,
        public readonly string $primaryTypeCode,
        public readonly ?string $secondaryTypeCode,
    ) {
    }
}


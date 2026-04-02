<?php

declare(strict_types=1);

namespace App\ReferenceData\Dto;

/**
 * Minimal reference contract for an imported Pokemon (V1).
 */
final class PokemonReferenceData
{
    /**
     * @param int $hp
     * @param int $atk
     * @param int $def
     * @param int $satk
     * @param int $sdef
     * @param int $spe
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


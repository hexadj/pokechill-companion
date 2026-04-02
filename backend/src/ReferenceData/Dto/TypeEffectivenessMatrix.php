<?php

declare(strict_types=1);

namespace App\ReferenceData\Dto;

use InvalidArgumentException;

/**
 * In-memory representation of the elementary type effectiveness matrix.
 *
 * Stored as V1 multipliers already scaled to x100:
 * - resistance (0.5) => 50
 * - neutral (1.0) => 100
 * - weakness (1.5) => 150
 * - immunity (0.0) => 0
 */
final class TypeEffectivenessMatrix
{
    /**
     * @var array<string, array<string, int>> [attackingTypeCode][defendingTypeCode] => multiplierX100
     */
    private array $matrix;

    /**
     * @param array<string, array<string, int>> $matrix
     */
    public function __construct(array $matrix)
    {
        $this->matrix = $matrix;
    }

    /**
     * @throws InvalidArgumentException when an unknown type code is requested.
     */
    public function getMultiplierX100(string $attackingTypeCode, string $defendingTypeCode): int
    {
        $attackingTypeCode = strtolower($attackingTypeCode);
        $defendingTypeCode = strtolower($defendingTypeCode);

        if (!isset($this->matrix[$attackingTypeCode])) {
            throw new InvalidArgumentException(sprintf('Unknown attacking type code "%s".', $attackingTypeCode));
        }
        if (!isset($this->matrix[$attackingTypeCode][$defendingTypeCode])) {
            throw new InvalidArgumentException(sprintf('Unknown defending type code "%s" for attacker "%s".', $defendingTypeCode, $attackingTypeCode));
        }

        return $this->matrix[$attackingTypeCode][$defendingTypeCode];
    }

    /**
     * Export all ordered pairs for seeding.
     *
     * @return array<array{string,string,int}> list of [attackingTypeCode, defendingTypeCode, multiplierX100]
     */
    public function toRows(): array
    {
        $rows = [];

        foreach ($this->matrix as $attackingTypeCode => $defenders) {
            foreach ($defenders as $defendingTypeCode => $multiplierX100) {
                $rows[] = [$attackingTypeCode, $defendingTypeCode, $multiplierX100];
            }
        }

        return $rows;
    }
}


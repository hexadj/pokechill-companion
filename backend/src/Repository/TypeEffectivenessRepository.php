<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TypeEffectiveness;
use App\ReferenceData\Dto\TypeEffectivenessMatrix;
use Doctrine\ORM\EntityManagerInterface;

class TypeEffectivenessRepository
{
    private ?TypeEffectivenessMatrix $matrix = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws \RuntimeException when the matrix is incomplete.
     */
    public function getMatrix(): TypeEffectivenessMatrix
    {
        if ($this->matrix !== null) {
            return $this->matrix;
        }

        $typeCodes = \array_flip(\App\ReferenceData\Seeder\TypeEffectivenessMatrixBuilder::getTypeCodes());

        $rows = $this->entityManager->createQuery(
            '
            SELECT
                te.multiplierX100 AS multiplierX100,
                attackingType.code AS attackingCode,
                defendingType.code AS defendingCode
            FROM ' . TypeEffectiveness::class . ' te
            JOIN te.attackingType attackingType
            JOIN te.defendingType defendingType
            '
        )->getArrayResult();

        $matrix = [];
        foreach ($typeCodes as $attackingCode => $_) {
            $matrix[$attackingCode] = [];
        }

        foreach ($rows as $row) {
            $attackingCode = strtolower($row['attackingCode']);
            $defendingCode = strtolower($row['defendingCode']);
            $matrix[$attackingCode][$defendingCode] = (int) $row['multiplierX100'];
        }

        // Validate completeness for determinism: 18x18.
        foreach ($typeCodes as $attackingCode => $_) {
            foreach ($typeCodes as $defendingCode => $_2) {
                if (!isset($matrix[$attackingCode][$defendingCode])) {
                    throw new \RuntimeException(sprintf(
                        'Type effectiveness matrix incomplete: missing %s vs %s.',
                        $attackingCode,
                        $defendingCode
                    ));
                }
            }
        }

        $this->matrix = new TypeEffectivenessMatrix($matrix);

        return $this->matrix;
    }
}


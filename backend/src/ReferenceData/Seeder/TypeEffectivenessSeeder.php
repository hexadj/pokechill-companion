<?php

declare(strict_types=1);

namespace App\ReferenceData\Seeder;

use App\Entity\Type;
use App\Entity\TypeEffectiveness;
use Doctrine\ORM\EntityManagerInterface;

final class TypeEffectivenessSeeder
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{inserted:int}
     */
    public function seed(): array
    {
        $typeCodes = TypeEffectivenessMatrixBuilder::getTypeCodes();

        /** @var array<string, Type> $typeByCode */
        $typeByCode = [];
        $types = $this->entityManager->getRepository(Type::class)->findBy([
            'code' => $typeCodes,
        ]);
        foreach ($types as $type) {
            $typeByCode[$type->getCode()] = $type;
        }

        // The command should run after TypeSeeder, but fail loudly if types are missing.
        $missing = array_values(array_diff($typeCodes, array_keys($typeByCode)));
        if ($missing !== []) {
            $missingStr = implode(', ', $missing);
            throw new \RuntimeException(sprintf('Missing type rows for codes: %s', $missingStr));
        }

        $matrix = TypeEffectivenessMatrixBuilder::build();

        $inserted = 0;
        $this->entityManager->wrapInTransaction(function () use ($matrix, $typeByCode, &$inserted): void {
            // Idempotence: rebuild the full matrix atomically.
            $this->entityManager->getConnection()->executeStatement('DELETE FROM type_effectiveness');

            foreach ($matrix->toRows() as [$attackingCode, $defendingCode, $multiplierX100]) {
                $entity = new TypeEffectiveness();
                $entity->setAttackingType($typeByCode[$attackingCode]);
                $entity->setDefendingType($typeByCode[$defendingCode]);
                $entity->setMultiplierX100($multiplierX100);
                $this->entityManager->persist($entity);
                $inserted++;
            }
        });

        return [
            'inserted' => $inserted,
        ];
    }
}

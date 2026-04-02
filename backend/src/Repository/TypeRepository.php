<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Type;
use Doctrine\ORM\EntityManagerInterface;

final class TypeRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findByCode(string $code): ?Type
    {
        $code = strtolower($code);

        /** @var Type|null $type */
        $type = $this->entityManager->getRepository(Type::class)->findOneBy([
            'code' => $code,
        ]);

        return $type;
    }

    public function findIdByCode(string $code): ?int
    {
        $type = $this->findByCode($code);

        return $type?->getId();
    }

    /**
     * @param string[] $codes
     * @return array<string, Type> keyed by lower-cased code.
     */
    public function findByCodes(array $codes): array
    {
        $codes = array_values(array_unique(array_map(static fn (string $c): string => strtolower(trim($c)), $codes)));

        if ($codes === []) {
            return [];
        }

        /** @var array<string, Type> $result */
        $result = [];

        $types = $this->entityManager->getRepository(Type::class)->findBy([
            'code' => $codes,
        ]);

        foreach ($types as $type) {
            if (!$type instanceof Type) {
                continue;
            }
            $result[strtolower($type->getCode())] = $type;
        }

        return $result;
    }
}


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
}


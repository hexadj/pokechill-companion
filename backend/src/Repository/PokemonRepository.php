<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Pokemon;
use Doctrine\ORM\EntityManagerInterface;

final class PokemonRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<string, Pokemon> keyed by sourceKey
     */
    public function findActiveBySourceKeys(array $sourceKeys): array
    {
        $sourceKeys = array_values(array_unique(array_map('strval', $sourceKeys)));

        if ($sourceKeys === []) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('p', 'primaryType', 'secondaryType')
            ->from(Pokemon::class, 'p')
            ->join('p.primaryType', 'primaryType')
            ->leftJoin('p.secondaryType', 'secondaryType')
            ->where('p.isActive = true')
            ->andWhere('p.sourceKey IN (:sourceKeys)')
            ->setParameter('sourceKeys', $sourceKeys);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $qb->getQuery()->getResult();

        $result = [];
        foreach ($rows as $pokemon) {
            if (!$pokemon instanceof Pokemon) {
                continue;
            }
            $result[$pokemon->getSourceKey()] = $pokemon;
        }

        return $result;
    }

    /**
     * @return Pokemon[]
     */
    public function findAllActive(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('p', 'primaryType', 'secondaryType')
            ->from(Pokemon::class, 'p')
            ->join('p.primaryType', 'primaryType')
            ->leftJoin('p.secondaryType', 'secondaryType')
            ->where('p.isActive = true')
            ->orderBy('p.name', 'ASC');

        /** @var Pokemon[] $pokemons */
        $pokemons = $qb->getQuery()->getResult();

        return $pokemons;
    }
}


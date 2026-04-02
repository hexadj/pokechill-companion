<?php

declare(strict_types=1);

namespace App\ReferenceData\Seeder;

use App\Entity\Type;
use Doctrine\ORM\EntityManagerInterface;

final class TypeSeeder
{
    private const TYPES = [
        'normal' => 'Normal',
        'fire' => 'Fire',
        'water' => 'Water',
        'grass' => 'Grass',
        'electric' => 'Electric',
        'ice' => 'Ice',
        'fighting' => 'Fighting',
        'poison' => 'Poison',
        'ground' => 'Ground',
        'flying' => 'Flying',
        'psychic' => 'Psychic',
        'bug' => 'Bug',
        'rock' => 'Rock',
        'ghost' => 'Ghost',
        'dragon' => 'Dragon',
        'dark' => 'Dark',
        'steel' => 'Steel',
        'fairy' => 'Fairy',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Seed or update the 18 Pokémon types.
     *
     * @return array{created:int,updated:int,total:int}
     */
    public function seed(): array
    {
        $created = 0;
        $updated = 0;

        /** @var array<string, Type> $existingByCode */
        $existingTypes = $this->entityManager->getRepository(Type::class)->findBy([
            'code' => array_keys(self::TYPES),
        ]);

        $existingByCode = [];
        foreach ($existingTypes as $type) {
            $existingByCode[$type->getCode()] = $type;
        }

        foreach (self::TYPES as $code => $label) {
            if (!isset($existingByCode[$code])) {
                $type = new Type();
                $type->setCode($code);
                $type->setLabel($label);
                $this->entityManager->persist($type);
                $created++;
                continue;
            }

            $type = $existingByCode[$code];
            if ($type->getLabel() !== $label) {
                $type->setLabel($label);
                $this->entityManager->persist($type);
                $updated++;
            }
        }

        $this->entityManager->flush();

        return [
            'created' => $created,
            'updated' => $updated,
            'total' => count(self::TYPES),
        ];
    }
}


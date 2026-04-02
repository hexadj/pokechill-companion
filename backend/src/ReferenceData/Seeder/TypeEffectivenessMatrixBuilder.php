<?php

declare(strict_types=1);

namespace App\ReferenceData\Seeder;

use App\ReferenceData\Dto\TypeEffectivenessMatrix;

/**
 * Builds the elementary type effectiveness matrix for V1.
 *
 * Mapping rules (V1):
 * - resistance (0.5) => 50
 * - neutral (1.0) => 100
 * - weakness (1.5) => 150
 * - immunity (0.0) => 0
 */
final class TypeEffectivenessMatrixBuilder
{
    /**
     * @return array<int, string>
     */
    public static function getTypeCodes(): array
    {
        // Keep in sync with TypeSeeder (Phase 2).
        return [
            'normal',
            'fire',
            'water',
            'grass',
            'electric',
            'ice',
            'fighting',
            'poison',
            'ground',
            'flying',
            'psychic',
            'bug',
            'rock',
            'ghost',
            'dragon',
            'dark',
            'steel',
            'fairy',
        ];
    }

    /**
     * @return TypeEffectivenessMatrix
     */
    public static function build(): TypeEffectivenessMatrix
    {
        $typeCodes = self::getTypeCodes();

        // Attacker perspective: list of defending types that receive 2x damage.
        $superEffectiveAgainst = [
            'normal' => [],
            'fire' => ['grass', 'ice', 'bug', 'steel'],
            'water' => ['fire', 'ground', 'rock'],
            'grass' => ['water', 'ground', 'rock'],
            'electric' => ['water', 'flying'],
            'ice' => ['grass', 'ground', 'flying', 'dragon'],
            'fighting' => ['normal', 'ice', 'rock', 'dark', 'steel'],
            'poison' => ['grass', 'fairy'],
            'ground' => ['fire', 'electric', 'poison', 'rock', 'steel'],
            'flying' => ['grass', 'fighting', 'bug'],
            'psychic' => ['fighting', 'poison'],
            'bug' => ['grass', 'psychic', 'dark'],
            'rock' => ['fire', 'ice', 'flying', 'bug'],
            'ghost' => ['psychic', 'ghost'],
            'dragon' => ['dragon'],
            'dark' => ['psychic', 'ghost'],
            'steel' => ['ice', 'rock', 'fairy'],
            'fairy' => ['fighting', 'dragon', 'dark'],
        ];

        // Attacker perspective: list of defending types that receive 0.5x damage.
        $notVeryEffectiveAgainst = [
            'normal' => ['rock', 'steel'],
            'fire' => ['fire', 'water', 'rock', 'dragon'],
            'water' => ['water', 'grass', 'dragon'],
            'grass' => ['fire', 'grass', 'poison', 'flying', 'bug', 'dragon', 'steel'],
            'electric' => ['electric', 'grass', 'dragon'],
            'ice' => ['fire', 'water', 'ice', 'steel'],
            'fighting' => ['poison', 'flying', 'psychic', 'bug', 'fairy'],
            'poison' => ['poison', 'ground', 'rock', 'ghost'],
            'ground' => ['grass', 'bug'],
            'flying' => ['electric', 'rock', 'steel'],
            'psychic' => ['psychic', 'steel'],
            'bug' => ['fire', 'fighting', 'poison', 'flying', 'ghost', 'steel', 'fairy'],
            'rock' => ['fighting', 'ground', 'steel'],
            'ghost' => ['dark'],
            'dragon' => ['steel'],
            'dark' => ['fighting', 'dark', 'fairy'],
            'steel' => ['fire', 'water', 'electric', 'steel'],
            'fairy' => ['fire', 'poison', 'steel'],
        ];

        /**
         * Defender perspective:
         * immuneToAttackersByDefender[defenderTypeCode] = list of attacking type codes
         * that do 0 damage to this defender type.
         *
         * Immunities override super/not-very effectiveness.
         *
         * V1 rule: immunity => multiplier_x100 = 0.
         *
         * Note: these correspond to standard mainline Pokémon immunities.
         *
         * - normal is immune to ghost
         * - fighting is immune to ghost
         * - flying is immune to ground
         * - ground is immune to electric
         * - ghost is immune to normal and fighting
         * - steel is immune to poison
         * - dark is immune to psychic
         * - fairy is immune to dragon
         *
         * @var array<string, array<int, string>>
         */
        $immuneToAttackersByDefender = [
            'normal' => ['ghost'],
            'fighting' => ['ghost'],
            'flying' => ['ground'],
            'ground' => ['electric'],
            'ghost' => ['normal', 'fighting'],
            'steel' => ['poison'],
            'dark' => ['psychic'],
            'fairy' => ['dragon'],
        ];

        $matrix = [];

        foreach ($typeCodes as $attackingTypeCode) {
            $matrix[$attackingTypeCode] = [];

            foreach ($typeCodes as $defendingTypeCode) {
                $multiplierX100 = 100; // neutral by default

                // Immunity takes precedence.
                $immuneAttackers = $immuneToAttackersByDefender[$defendingTypeCode] ?? [];
                if (in_array($attackingTypeCode, $immuneAttackers, true)) {
                    $multiplierX100 = 0;
                } else {
                    if (in_array($defendingTypeCode, $superEffectiveAgainst[$attackingTypeCode] ?? [], true)) {
                        $multiplierX100 = 150;
                    } elseif (in_array($defendingTypeCode, $notVeryEffectiveAgainst[$attackingTypeCode] ?? [], true)) {
                        $multiplierX100 = 50;
                    }
                }

                if (!in_array($multiplierX100, [0, 50, 100, 150], true)) {
                    throw new \RuntimeException(sprintf(
                        'Unexpected multiplierX100 "%d" for %s vs %s.',
                        $multiplierX100,
                        $attackingTypeCode,
                        $defendingTypeCode
                    ));
                }

                $matrix[$attackingTypeCode][$defendingTypeCode] = $multiplierX100;
            }
        }

        return new TypeEffectivenessMatrix($matrix);
    }
}


<?php

declare(strict_types=1);

namespace App\ReferenceData\Import\Dto;

/**
 * One {@code areas.*} block in file order (for {@code setSearchTags} iteration).
 */
final class PokechillAreaObtainabilityRow
{
    /**
     * @param list<string> $wildSpawnKeys   keys appearing in spawns common/uncommon/rare when type is wild
     * @param list<string> $eventSpawnKeys  same for event areas
     * @param list<string> $rewardKeys      pkmn keys in reward arrays
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $type,
        public readonly bool $uncatchable,
        public readonly bool $encounter,
        public readonly array $wildSpawnKeys,
        public readonly array $eventSpawnKeys,
        public readonly ?string $encounterSlot1Key,
        public readonly array $rewardKeys,
    ) {
    }
}

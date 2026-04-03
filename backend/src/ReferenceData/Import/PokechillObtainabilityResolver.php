<?php

declare(strict_types=1);

namespace App\ReferenceData\Import;

use App\ReferenceData\Import\Dto\PokechillAreaObtainabilityRow;

/**
 * PHP port of {@code setSearchTags()} from dictionarySearch.js (Pokechill).
 */
final class PokechillObtainabilityResolver
{
    public function __construct(
        private readonly PokechillEvolutionGraphBuilder $evolutionGraphBuilder,
    ) {
    }

    /**
     * @param list<string>                                                                 $allPkmnKeys file order (same as {@code for (const e in pkmn)})
     * @param list<PokechillAreaObtainabilityRow>                                          $areas
     * @param array{common: list<string>, uncommon: list<string>, rare: list<string>, frontierExclusive: list<string>} $pools
     * @param list<string>                                                                 $martKeys
     * @param array<string, list<string>>                                                  $undirectedAdjacency
     *
     * @return array<string, array{code: string|null, isObtainable: bool}>
     */
    public function resolve(
        array $allPkmnKeys,
        array $areas,
        array $pools,
        array $martKeys,
        array $undirectedAdjacency,
    ): array {
        /** @var array<string, string|null> $tag */
        $tag = [];

        $parkSet = array_flip(array_merge($pools['common'], $pools['uncommon'], $pools['rare']));
        $frontierSet = array_flip($pools['frontierExclusive']);

        foreach ($allPkmnKeys as $e) {
            foreach ($areas as $area) {
                if ($area->type === 'wild') {
                    if ($this->keyInList($e, $area->wildSpawnKeys)) {
                        $tag[$e] = 'wild';
                    }
                }

                if ($area->type === 'event' && !$area->uncatchable) {
                    if ($this->keyInList($e, $area->eventSpawnKeys)) {
                        $tag[$e] = 'event';
                    }
                }

                if ($area->encounter) {
                    if ($area->encounterSlot1Key === $e || $this->keyInList($e, $area->rewardKeys)) {
                        $tag[$e] = 'event';
                    }
                }
            }

            if (isset($parkSet[$e])) {
                $tag[$e] = 'park';
            }
            if (isset($frontierSet[$e])) {
                $tag[$e] = 'frontier';
            }
        }

        foreach ($martKeys as $mk) {
            $tag[$mk] = 'mart';
        }

        foreach ($allPkmnKeys as $e) {
            if (($tag[$e] ?? null) !== null) {
                continue;
            }

            $family = $this->evolutionGraphBuilder->connectedComponent($e, $undirectedAdjacency);
            $familyHasDirectObtainable = false;
            foreach ($family as $member) {
                $t = $tag[$member] ?? null;
                if ($t !== null && $t !== 'unobtainable') {
                    $familyHasDirectObtainable = true;
                    break;
                }
            }

            if (!$familyHasDirectObtainable) {
                foreach ($family as $member) {
                    if (($tag[$member] ?? null) === null) {
                        $tag[$member] = 'unobtainable';
                    }
                }
            }
        }

        foreach ($allPkmnKeys as $e) {
            if ($e === 'arceus') {
                $tag[$e] = 'arceus';
            }
        }

        $out = [];
        foreach ($allPkmnKeys as $e) {
            $code = $tag[$e] ?? null;
            $out[$e] = [
                'code' => $code,
                'isObtainable' => $code !== 'unobtainable',
            ];
        }

        return $out;
    }

    /**
     * @param list<string> $list
     */
    private function keyInList(string $key, array $list): bool
    {
        foreach ($list as $item) {
            if ($item === $key) {
                return true;
            }
        }

        return false;
    }
}

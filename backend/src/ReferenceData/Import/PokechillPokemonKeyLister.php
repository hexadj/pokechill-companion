<?php

declare(strict_types=1);

namespace App\ReferenceData\Import;

/**
 * Lists every {@code pkmn.<key>} defined in pkmnDictionary.js (including hidden entries).
 */
final class PokechillPokemonKeyLister
{
    /**
     * @return list<string>
     */
    public function listAllKeys(string $pokechillJs): array
    {
        $js = PokechillJsParsing::stripJsCommentsPreserveLength($pokechillJs);
        preg_match_all(
            '/pkmn\\.([A-Za-z_][A-Za-z0-9_]*)\\s*=\\s*\\{/m',
            $js,
            $m,
        );

        $ordered = [];
        $seen = [];
        foreach ($m[1] as $k) {
            if (isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $ordered[] = $k;
        }

        return $ordered;
    }
}

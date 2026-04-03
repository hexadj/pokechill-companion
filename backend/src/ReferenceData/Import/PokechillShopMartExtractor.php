<?php

declare(strict_types=1);

namespace App\ReferenceData\Import;

/**
 * Extracts mart-eligible Pokemon keys from shop.js ({@code pkmn: pkmn.<key>.id}).
 */
final class PokechillShopMartExtractor
{
    /**
     * @return list<string>
     */
    public function extractMartSourceKeys(string $shopJs): array
    {
        $js = PokechillJsParsing::stripJsCommentsPreserveLength($shopJs);
        preg_match_all('/\\bpkmn\\s*:\\s*pkmn\\.([A-Za-z_][A-Za-z0-9_]*)\\.id\\b/', $js, $m);
        $keys = [];
        foreach ($m[1] as $k) {
            $keys[$k] = true;
        }

        return array_keys($keys);
    }
}

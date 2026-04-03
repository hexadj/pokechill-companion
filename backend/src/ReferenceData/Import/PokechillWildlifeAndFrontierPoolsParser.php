<?php

declare(strict_types=1);

namespace App\ReferenceData\Import;

use RuntimeException;

/**
 * Parses {@code wildlifePoolCommon|Uncommon|Rare} and {@code exclusiveFrontierPkmn} from areasDictionary.js.
 *
 * Upstream assigns {@code pkmn[i].id = i} (source key string), so pool entries use {@code pkmn.foo.id}.
 */
final class PokechillWildlifeAndFrontierPoolsParser
{
    /**
     * @return array{common: list<string>, uncommon: list<string>, rare: list<string>, frontierExclusive: list<string>}
     */
    public function parse(string $areasDictionaryJs): array
    {
        $js = PokechillJsParsing::stripJsCommentsPreserveLength($areasDictionaryJs);

        return [
            'common' => $this->parseNamedPool($js, 'wildlifePoolCommon'),
            'uncommon' => $this->parseNamedPool($js, 'wildlifePoolUncommon'),
            'rare' => $this->parseNamedPool($js, 'wildlifePoolRare'),
            'frontierExclusive' => $this->parseFrontierExclusive($js),
        ];
    }

    /**
     * @return list<string>
     */
    private function parseNamedPool(string $js, string $constName): array
    {
        $needle = 'const '.$constName.' = [';
        $pos = strpos($js, $needle);
        if ($pos === false) {
            throw new RuntimeException(sprintf('Missing %s in areas dictionary.', $constName));
        }

        $openBracket = strpos($js, '[', $pos);
        if ($openBracket === false) {
            throw new RuntimeException(sprintf('Malformed %s array.', $constName));
        }

        [$inner] = PokechillJsParsing::extractBalancedSquareBlock($js, $openBracket);

        return $this->extractPkmnIdKeys($inner);
    }

    /**
     * @return list<string>
     */
    private function parseFrontierExclusive(string $js): array
    {
        $needle = 'const exclusiveFrontierPkmn = [';
        $pos = strpos($js, $needle);
        if ($pos === false) {
            throw new RuntimeException('Missing exclusiveFrontierPkmn in areas dictionary.');
        }

        $openBracket = strpos($js, '[', $pos);
        if ($openBracket === false) {
            throw new RuntimeException('Malformed exclusiveFrontierPkmn array.');
        }

        [$inner] = PokechillJsParsing::extractBalancedSquareBlock($js, $openBracket);

        preg_match_all('/\\bpkmn\\.([A-Za-z_][A-Za-z0-9_]*)(?=\\s*[,\\]])/', $inner, $m);
        $keys = [];
        foreach ($m[1] as $k) {
            $keys[$k] = true;
        }

        return array_keys($keys);
    }

    /**
     * @return list<string>
     */
    private function extractPkmnIdKeys(string $arrayInner): array
    {
        preg_match_all('/\\bpkmn\\.([A-Za-z_][A-Za-z0-9_]*)\\.id\\b/', $arrayInner, $m);
        $keys = [];
        foreach ($m[1] as $k) {
            $keys[$k] = true;
        }

        return array_keys($keys);
    }
}

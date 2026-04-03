<?php

declare(strict_types=1);

namespace App\ReferenceData\Import;

use RuntimeException;

/**
 * Builds evolution adjacency from static {@code pkmnDictionary.js} (evolve() forward edges + reverse).
 *
 * Mirrors {@code getEvolutionFamily} connectivity (undirected) from explore.js.
 */
final class PokechillEvolutionGraphBuilder
{
    /**
     * @return array<string, list<string>> undirected: each key maps to unique neighbor keys
     */
    public function buildUndirectedAdjacency(string $pokechillJs): array
    {
        $pokechillJs = PokechillJsParsing::stripJsCommentsPreserveLength($pokechillJs);

        $matches = [];
        preg_match_all(
            '/pkmn\\.([A-Za-z_][A-Za-z0-9_]*)\\s*=\\s*\\{/m',
            $pokechillJs,
            $matches,
            PREG_OFFSET_CAPTURE,
        );

        if (!isset($matches[1])) {
            throw new RuntimeException('Unable to detect Pokechill pkmn entries for evolution graph.');
        }

        $forward = [];
        foreach ($matches[1] as $match) {
            $sourceKey = (string) $match[0];
            $startPos = (int) $match[1];
            $openingBracePos = PokechillJsParsing::findNextChar($pokechillJs, '{', $startPos);
            if ($openingBracePos === null) {
                continue;
            }

            $objectLiteral = PokechillJsParsing::extractBalancedBlock($pokechillJs, $openingBracePos);
            $targets = $this->extractEvolveTargets($objectLiteral);
            if ($targets !== []) {
                $forward[$sourceKey] = $targets;
            }
        }

        $adj = [];
        foreach ($forward as $from => $tos) {
            foreach ($tos as $to) {
                $adj[$from][$to] = true;
                $adj[$to][$from] = true;
            }
        }

        $result = [];
        foreach ($adj as $k => $neighbors) {
            $result[$k] = array_keys($neighbors);
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    private function extractEvolveTargets(string $objectLiteral): array
    {
        if (preg_match('/\\bevolve\\s*:\\s*function\\s*\\(\\s*\\)\\s*\\{/s', $objectLiteral, $m, PREG_OFFSET_CAPTURE) !== 1) {
            return [];
        }

        $bracePos = $m[0][1] + strlen($m[0][0]) - 1;
        if (($objectLiteral[$bracePos] ?? '') !== '{') {
            return [];
        }

        $evolveBody = PokechillJsParsing::extractBalancedBlock($objectLiteral, $bracePos);
        preg_match_all('/\\bpkmn\\.([A-Za-z_][A-Za-z0-9_]*)/', $evolveBody, $refs);

        $targets = [];
        foreach ($refs[1] as $t) {
            $targets[$t] = true;
        }

        return array_keys($targets);
    }

    /**
     * @param array<string, list<string>> $adjacency
     *
     * @return list<string> all keys in the same connected component as {@code $sourceKey}
     */
    public function connectedComponent(string $sourceKey, array $adjacency): array
    {
        if (!isset($adjacency[$sourceKey])) {
            return [$sourceKey];
        }

        $seen = [$sourceKey => true];
        $stack = [$sourceKey];
        while ($stack !== []) {
            $current = array_pop($stack);
            foreach ($adjacency[$current] ?? [] as $n) {
                if (!isset($seen[$n])) {
                    $seen[$n] = true;
                    $stack[] = $n;
                }
            }
        }

        return array_keys($seen);
    }
}

<?php

declare(strict_types=1);

namespace App\ReferenceData\Import;

use App\ReferenceData\Import\Dto\PokechillAreaObtainabilityRow;
use RuntimeException;

/**
 * Extracts ordered {@code areas.*} blocks from areasDictionary.js for obtainability tagging.
 */
final class PokechillAreasObtainabilityExtractor
{
    /**
     * @return list<PokechillAreaObtainabilityRow>
     */
    public function extractRows(string $areasDictionaryJs): array
    {
        $js = PokechillJsParsing::stripJsCommentsPreserveLength($areasDictionaryJs);

        $matches = [];
        preg_match_all(
            '/areas\\.([a-zA-Z_][a-zA-Z0-9_]*)\\s*=\\s*\\{/m',
            $js,
            $matches,
            PREG_OFFSET_CAPTURE,
        );

        if (!isset($matches[1])) {
            throw new RuntimeException('Unable to detect areas.* blocks.');
        }

        $rows = [];
        foreach ($matches[1] as $idx => $nameMatch) {
            $name = (string) $nameMatch[0];
            $startPos = (int) $nameMatch[1];
            $openingBracePos = PokechillJsParsing::findNextChar($js, '{', $startPos);
            if ($openingBracePos === null) {
                continue;
            }

            $block = PokechillJsParsing::extractBalancedBlock($js, $openingBracePos);
            $rows[] = $this->parseAreaBlock($name, $block);
        }

        return $rows;
    }

    private function parseAreaBlock(string $name, string $blockBody): PokechillAreaObtainabilityRow
    {
        $type = $this->extractType($blockBody);
        $uncatchable = preg_match('/\\buncatchable\\s*:\\s*true\\b/', $blockBody) === 1;
        $encounter = preg_match('/\\bencounter\\s*:\\s*true\\b/', $blockBody) === 1;

        $spawnsInner = $this->extractSpawnsBlockInner($blockBody);
        $wildSpawnKeys = $spawnsInner !== null ? $this->collectSpawnKeys($spawnsInner) : [];
        $eventSpawnKeys = $wildSpawnKeys;

        $encounterSlot1Key = $this->extractTeamSlot1Key($blockBody);
        $rewardKeys = $this->extractRewardKeys($blockBody);

        return new PokechillAreaObtainabilityRow(
            name: $name,
            type: $type,
            uncatchable: $uncatchable,
            encounter: $encounter,
            wildSpawnKeys: $wildSpawnKeys,
            eventSpawnKeys: $eventSpawnKeys,
            encounterSlot1Key: $encounterSlot1Key,
            rewardKeys: $rewardKeys,
        );
    }

    private function extractType(string $blockBody): ?string
    {
        if (preg_match('/\\btype\\s*:\\s*`([^`]*)`/m', $blockBody, $m) === 1) {
            return $m[1] === '' ? null : $m[1];
        }
        if (preg_match('/\\btype\\s*:\\s*"([^"]*)"/m', $blockBody, $m) === 1) {
            return $m[1] === '' ? null : $m[1];
        }
        if (preg_match('/\\btype\\s*:\\s*\'([^\']*)\'/m', $blockBody, $m) === 1) {
            return $m[1] === '' ? null : $m[1];
        }

        return null;
    }

    private function extractSpawnsBlockInner(string $blockBody): ?string
    {
        if (preg_match('/\\bspawns\\s*:\\s*\\{/m', $blockBody, $m, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        $bracePos = PokechillJsParsing::findNextChar($blockBody, '{', (int) $m[0][1]);
        if ($bracePos === null) {
            return null;
        }

        $spawnsBlock = PokechillJsParsing::extractBalancedBlock($blockBody, $bracePos);

        return substr($spawnsBlock, 1, -1);
    }

    /**
     * @return list<string>
     */
    private function collectSpawnKeys(string $spawnsInner): array
    {
        $keys = [];
        foreach (['common', 'uncommon', 'rare'] as $tier) {
            $inner = $this->extractArrayAfterKey($spawnsInner, $tier);
            if ($inner === null) {
                continue;
            }

            preg_match_all('/\\bpkmn\\.([A-Za-z_][A-Za-z0-9_]*)/', $inner, $m);
            foreach ($m[1] as $k) {
                $keys[$k] = true;
            }
        }

        return array_keys($keys);
    }

    private function extractArrayAfterKey(string $haystack, string $key): ?string
    {
        if (preg_match('/\\b'.preg_quote($key, '/').'\\s*:\\s*\\[/m', $haystack, $m, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        $openBracket = PokechillJsParsing::findNextChar($haystack, '[', (int) $m[0][1]);
        if ($openBracket === null) {
            return null;
        }

        [$inner] = PokechillJsParsing::extractBalancedSquareBlock($haystack, $openBracket);

        return $inner;
    }

    private function extractTeamSlot1Key(string $blockBody): ?string
    {
        if (preg_match('/\\bteam\\s*:\\s*\\{/m', $blockBody, $m, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        $bracePos = PokechillJsParsing::findNextChar($blockBody, '{', (int) $m[0][1]);
        if ($bracePos === null) {
            return null;
        }

        $teamBlock = PokechillJsParsing::extractBalancedBlock($blockBody, $bracePos);
        $teamInner = substr($teamBlock, 1, -1);

        if (preg_match('/\\bslot1\\s*:\\s*pkmn\\.([A-Za-z_][A-Za-z0-9_]*)/m', $teamInner, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function extractRewardKeys(string $blockBody): array
    {
        if (preg_match('/\\breward\\s*:\\s*\\[/m', $blockBody, $m, PREG_OFFSET_CAPTURE) !== 1) {
            return [];
        }

        $openBracket = PokechillJsParsing::findNextChar($blockBody, '[', (int) $m[0][1]);
        if ($openBracket === null) {
            return [];
        }

        [$inner] = PokechillJsParsing::extractBalancedSquareBlock($blockBody, $openBracket);
        preg_match_all('/\\bpkmn\\.([A-Za-z_][A-Za-z0-9_]*)/', $inner, $m);

        $keys = [];
        foreach ($m[1] as $k) {
            $keys[$k] = true;
        }

        return array_keys($keys);
    }
}

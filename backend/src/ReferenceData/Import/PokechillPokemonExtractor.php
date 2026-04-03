<?php

declare(strict_types=1);

namespace App\ReferenceData\Import;

use App\ReferenceData\Import\Dto\ExtractedPokechillPokemon;
use RuntimeException;

/**
 * Extracts Pokemon base stats and types from the upstream Pokechill JS dataset.
 *
 * No JS execution: we parse the static object literals in the source file.
 */
final class PokechillPokemonExtractor
{
    /**
     * @var string[]
     */
    private const BST_STATS = ['hp', 'atk', 'def', 'satk', 'sdef', 'spe'];

    /**
     * For V1 we exclude hidden entries from the extracted dataset.
     */
    private const EXCLUDE_HIDDEN = true;

    /**
     * @return array{pokemons: ExtractedPokechillPokemon[], sourcePokemonCount:int, extractedPokemonCount:int, ignoredPokemonCount:int}
     */
    public function extract(string $pokechillJs): array
    {
        // Ignore commented-out pokemon entries.
        $pokechillJs = $this->stripJsCommentsPreserveLength($pokechillJs);

        $matches = [];
        preg_match_all(
            '/pkmn\\.([A-Za-z_][A-Za-z0-9_]*)\\s*=\\s*\\{/m',
            $pokechillJs,
            $matches,
            PREG_OFFSET_CAPTURE,
        );

        if (!isset($matches[1])) {
            throw new RuntimeException('Unable to detect Pokechill pkmn entries.');
        }

        $pokemons = [];
        $sourcePokemonCount = 0;
        $ignoredPokemonCount = 0;

        foreach ($matches[1] as $match) {
            $sourcePokemonCount++;
            $sourceKey = (string) $match[0];
            $startPos = (int) $match[1]; // start of group, not of the full match

            // Find the opening "{" of the object literal.
            // We search forward from the group start position for the next "{".
            $openingBracePos = $this->findNextChar($pokechillJs, '{', $startPos);
            if ($openingBracePos === null) {
                throw new RuntimeException(sprintf('Unable to locate object literal for "%s".', $sourceKey));
            }

            $objectLiteral = $this->extractBalancedBlock($pokechillJs, $openingBracePos);

            $isHidden = $this->extractBoolProperty($objectLiteral, 'hidden');
            if ($isHidden && self::EXCLUDE_HIDDEN) {
                $ignoredPokemonCount++;
                continue;
            }

            $typeCodes = $this->extractTypeCodes($objectLiteral);
            if ($typeCodes === null) {
                throw new RuntimeException(sprintf('Missing/invalid "type" for "%s".', $sourceKey));
            }
            if (\count($typeCodes) < 1 || \count($typeCodes) > 2) {
                throw new RuntimeException(sprintf('Unexpected number of types for "%s".', $sourceKey));
            }

            $primaryTypeCode = strtolower($typeCodes[0]);
            $secondaryTypeCode = null;
            if (\count($typeCodes) === 2) {
                $secondaryTypeCode = strtolower($typeCodes[1]);
                if ($secondaryTypeCode === $primaryTypeCode) {
                    $secondaryTypeCode = null;
                }
            }

            $bstObject = $this->extractBstObject($objectLiteral);
            if ($bstObject === null) {
                throw new RuntimeException(sprintf('Missing/invalid "bst" for "%s".', $sourceKey));
            }

            $stats = [];
            foreach (self::BST_STATS as $statKey) {
                $expr = $this->extractStatExpression($bstObject, $statKey);
                if ($expr === null) {
                    throw new RuntimeException(sprintf('Missing stat "%s" in "bst" for "%s".', $statKey, $sourceKey));
                }
                $stats[$statKey] = $this->evaluateStatExpressionToInt($expr, $statKey, $sourceKey);
            }

            $rename = $this->extractRename($objectLiteral);

            $pokemons[] = new ExtractedPokechillPokemon(
                sourceKey: $sourceKey,
                rename: $rename,
                hp: $stats['hp'],
                atk: $stats['atk'],
                def: $stats['def'],
                satk: $stats['satk'],
                sdef: $stats['sdef'],
                spe: $stats['spe'],
                primaryTypeCode: $primaryTypeCode,
                secondaryTypeCode: $secondaryTypeCode,
                isHidden: $isHidden,
            );
        }

        return [
            'pokemons' => $pokemons,
            'sourcePokemonCount' => $sourcePokemonCount,
            'extractedPokemonCount' => \count($pokemons),
            'ignoredPokemonCount' => $ignoredPokemonCount,
        ];
    }

    /**
     * Strips JS comments while preserving string length (by replacing comment chars with spaces).
     * This keeps regex offsets and indices stable.
     */
    private function stripJsCommentsPreserveLength(string $input): string
    {
        $len = strlen($input);
        $chars = str_split($input);

        $inSingleQuote = false;
        $inDoubleQuote = false;
        $inTemplate = false;
        $inLineComment = false;
        $inBlockComment = false;

        for ($i = 0; $i < $len; $i++) {
            $ch = $chars[$i];
            $next = $i + 1 < $len ? $chars[$i + 1] : '';

            if ($inLineComment) {
                if ($ch === "\n") {
                    $inLineComment = false;
                    continue;
                }
                $chars[$i] = ' ';
                continue;
            }

            if ($inBlockComment) {
                if ($ch === '*' && $next === '/') {
                    $inBlockComment = false;
                    $chars[$i] = ' ';
                    $chars[$i + 1] = ' ';
                    $i++;
                    continue;
                }
                $chars[$i] = ' ';
                continue;
            }

            if ($inSingleQuote) {
                if ($ch === "'" && $i > 0 && $chars[$i - 1] !== '\\') {
                    $inSingleQuote = false;
                }
                continue;
            }

            if ($inDoubleQuote) {
                if ($ch === '"' && $i > 0 && $chars[$i - 1] !== '\\') {
                    $inDoubleQuote = false;
                }
                continue;
            }

            if ($inTemplate) {
                if ($ch === '`' && $i > 0 && $chars[$i - 1] !== '\\') {
                    $inTemplate = false;
                }
                continue;
            }

            // Enter comments?
            if ($ch === '/' && $next === '/') {
                $inLineComment = true;
                $chars[$i] = ' ';
                $chars[$i + 1] = ' ';
                $i++;
                continue;
            }
            if ($ch === '/' && $next === '*') {
                $inBlockComment = true;
                $chars[$i] = ' ';
                $chars[$i + 1] = ' ';
                $i++;
                continue;
            }

            // Enter strings?
            if ($ch === "'" && !$inDoubleQuote && !$inTemplate) {
                $inSingleQuote = true;
                continue;
            }
            if ($ch === '"' && !$inSingleQuote && !$inTemplate) {
                $inDoubleQuote = true;
                continue;
            }
            if ($ch === '`' && !$inSingleQuote && !$inDoubleQuote) {
                $inTemplate = true;
                continue;
            }
        }

        return implode('', $chars);
    }

    /**
     * @return string|null
     */
    private function extractRename(string $objectLiteral): ?string
    {
        // rename: `tangela`,
        if (preg_match('/\\brename\\s*:\\s*`([^`]*)`/m', $objectLiteral, $m) === 1) {
            $value = trim($m[1]);
            return $value === '' ? null : $value;
        }

        // rename: 'tangela',
        if (preg_match('/\\brename\\s*:\\s*\'([^\']*)\'/m', $objectLiteral, $m) === 1) {
            $value = trim($m[1]);
            return $value === '' ? null : $value;
        }

        // rename: "tangela",
        if (preg_match('/\\brename\\s*:\\s*"([^"]*)"/m', $objectLiteral, $m) === 1) {
            $value = trim($m[1]);
            return $value === '' ? null : $value;
        }

        return null;
    }

    /**
     * @return bool
     */
    private function extractBoolProperty(string $objectLiteral, string $property): bool
    {
        return preg_match(sprintf('/\\b%s\\s*:\\s*true\\b/m', preg_quote($property, '/')), $objectLiteral) === 1;
    }

    /**
     * @return string[]|null
     */
    private function extractTypeCodes(string $objectLiteral): ?array
    {
        $m = [];
        if (preg_match('/\\btype\\s*:\\s*\\[([^\\]]*)\\]/m', $objectLiteral, $m) !== 1) {
            return null;
        }

        $inner = $m[1];
        preg_match_all('/(["\'])(.*?)\\1/m', $inner, $arr);
        $values = [];
        foreach ($arr[2] as $v) {
            $v = trim((string) $v);
            if ($v !== '') {
                $values[] = $v;
            }
        }

        return $values;
    }

    /**
     * Extracts the nested `bst: { ... }` object literal.
     */
    private function extractBstObject(string $objectLiteral): ?string
    {
        $pos = strpos($objectLiteral, 'bst');
        if ($pos === false) {
            return null;
        }

        // Find `bst:` start.
        $bstPos = strpos($objectLiteral, 'bst:', $pos);
        if ($bstPos === false) {
            $bstPos = strpos($objectLiteral, 'bst :', $pos);
        }
        if ($bstPos === false) {
            // Fallback: regex for bst:
            if (preg_match('/\\bbst\\s*:\\s*\\{/', $objectLiteral, $m, PREG_OFFSET_CAPTURE) === 1) {
                $bstPos = $m[0][1];
            } else {
                return null;
            }
        }

        $openingBracePos = $this->findNextChar($objectLiteral, '{', $bstPos);
        if ($openingBracePos === null) {
            return null;
        }

        return $this->extractBalancedBlock($objectLiteral, $openingBracePos);
    }

    /**
     * @return string|null
     */
    private function extractStatExpression(string $bstObject, string $statKey): ?string
    {
        // Example: hp: 45,
        // Example: hp: 80*1.3,
        $pattern = sprintf('/\\b%s\\s*:\\s*([^,}]+)\\s*,?/m', preg_quote($statKey, '/'));
        if (preg_match($pattern, $bstObject, $m) !== 1) {
            return null;
        }

        return trim((string) $m[1]);
    }

    private function evaluateStatExpressionToInt(string $expr, string $statKey, string $sourceKey): int
    {
        $expr = trim($expr);

        // Plain integer.
        if (preg_match('/^\\d+$/', $expr) === 1) {
            $value = (int) $expr;
            if ($value < 0) {
                throw new RuntimeException(sprintf('Invalid %s=%d for "%s".', $statKey, $value, $sourceKey));
            }

            return $value;
        }

        // Multiplication: A*B
        if (preg_match('/^(\\d+)\\s*\\*\\s*(\\d+(?:\\.\\d+)?)$/', $expr, $m) === 1) {
            $a = (float) $m[1];
            $b = (float) $m[2];
            $valueFloat = $a * $b;
            $valueInt = (int) \round($valueFloat);

            if ($valueInt < 0) {
                throw new RuntimeException(sprintf(
                    'Invalid %s expression "%s" resolved to %d for "%s".',
                    $statKey,
                    $expr,
                    $valueInt,
                    $sourceKey
                ));
            }

            return $valueInt;
        }

        throw new RuntimeException(sprintf('Unsupported bst.%s expression "%s" for "%s".', $statKey, $expr, $sourceKey));
    }

    private function findNextChar(string $input, string $char, int $fromPos): ?int
    {
        $pos = strpos($input, $char, $fromPos);
        if ($pos === false) {
            return null;
        }

        return $pos;
    }

    /**
     * Extracts a balanced `{ ... }` block starting from an opening brace.
     *
     * It uses a light parser to ignore braces inside strings/comments.
     */
    private function extractBalancedBlock(string $input, int $openingBracePos): string
    {
        $len = strlen($input);
        if ($openingBracePos < 0 || $openingBracePos >= $len || $input[$openingBracePos] !== '{') {
            throw new RuntimeException('Internal error: expected opening brace.');
        }

        $depth = 0;
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $inTemplate = false;
        $inLineComment = false;
        $inBlockComment = false;

        $i = $openingBracePos;
        $start = $openingBracePos;

        while ($i < $len) {
            $ch = $input[$i];
            $next = $i + 1 < $len ? $input[$i + 1] : '';

            if ($inLineComment) {
                if ($ch === "\n") {
                    $inLineComment = false;
                }
                $i++;
                continue;
            }

            if ($inBlockComment) {
                if ($ch === '*' && $next === '/') {
                    $inBlockComment = false;
                    $i += 2;
                    continue;
                }
                $i++;
                continue;
            }

            if ($inSingleQuote) {
                if ($ch === "'" && $i > 0 && $input[$i - 1] !== '\\') {
                    $inSingleQuote = false;
                }
                $i++;
                continue;
            }

            if ($inDoubleQuote) {
                if ($ch === '"' && $i > 0 && $input[$i - 1] !== '\\') {
                    $inDoubleQuote = false;
                }
                $i++;
                continue;
            }

            if ($inTemplate) {
                if ($ch === '`' && $i > 0 && $input[$i - 1] !== '\\') {
                    $inTemplate = false;
                }
                $i++;
                continue;
            }

            // Enter comment?
            if (!$inSingleQuote && !$inDoubleQuote && !$inTemplate) {
                if ($ch === '/' && $next === '/') {
                    $inLineComment = true;
                    $i += 2;
                    continue;
                }
                if ($ch === '/' && $next === '*') {
                    $inBlockComment = true;
                    $i += 2;
                    continue;
                }
            }

            // Enter strings?
            if ($ch === "'" && !$inDoubleQuote && !$inTemplate) {
                $inSingleQuote = true;
                $i++;
                continue;
            }
            if ($ch === '"' && !$inSingleQuote && !$inTemplate) {
                $inDoubleQuote = true;
                $i++;
                continue;
            }
            if ($ch === '`' && !$inSingleQuote && !$inDoubleQuote) {
                $inTemplate = true;
                $i++;
                continue;
            }

            if ($ch === '{') {
                $depth++;
                $i++;
                continue;
            }

            if ($ch === '}') {
                $depth--;
                $i++;
                if ($depth === 0) {
                    return substr($input, $start, $i - $start);
                }
                continue;
            }

            $i++;
        }

        throw new RuntimeException('Unable to extract balanced block.');
    }
}

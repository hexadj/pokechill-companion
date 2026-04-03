<?php

declare(strict_types=1);

namespace App\ReferenceData\Import;

use RuntimeException;

/**
 * Shared static JS parsing helpers (no execution): comments, balanced blocks.
 */
final class PokechillJsParsing
{
    public static function stripJsCommentsPreserveLength(string $input): string
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

    public static function extractBalancedBlock(string $input, int $openingBracePos): string
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

    /**
     * @return array{0: string, 1: int} block content without outer brackets, end index after ']'
     */
    public static function extractBalancedSquareBlock(string $input, int $openingBracketPos): array
    {
        $len = strlen($input);
        if ($openingBracketPos < 0 || $openingBracketPos >= $len || $input[$openingBracketPos] !== '[') {
            throw new RuntimeException('Internal error: expected opening bracket.');
        }

        $depth = 0;
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $inTemplate = false;
        $inLineComment = false;
        $inBlockComment = false;

        $i = $openingBracketPos;
        $start = $openingBracketPos;

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

            if ($ch === '[') {
                $depth++;
                $i++;
                continue;
            }

            if ($ch === ']') {
                $depth--;
                $i++;
                if ($depth === 0) {
                    $inner = substr($input, $start + 1, $i - $start - 2);

                    return [$inner, $i];
                }
                continue;
            }

            $i++;
        }

        throw new RuntimeException('Unable to extract balanced square block.');
    }

    public static function findNextChar(string $input, string $char, int $fromPos): ?int
    {
        $pos = strpos($input, $char, $fromPos);
        if ($pos === false) {
            return null;
        }

        return $pos;
    }
}

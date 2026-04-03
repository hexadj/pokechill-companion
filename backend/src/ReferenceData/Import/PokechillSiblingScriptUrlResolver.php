<?php

declare(strict_types=1);

namespace App\ReferenceData\Import;

use RuntimeException;

/**
 * Resolves sibling script URLs from {@code POKECHILL_SOURCE_URL} (same directory / revision).
 */
final class PokechillSiblingScriptUrlResolver
{
    public function siblingFileUrl(string $pokechillSourceUrl, string $filename): string
    {
        $pokechillSourceUrl = trim($pokechillSourceUrl);
        if ($pokechillSourceUrl === '') {
            throw new RuntimeException('Empty Pokechill source URL.');
        }

        if (preg_match('#^(.*)[/\\\\][^/\\\\]+$#', $pokechillSourceUrl, $m) === 1) {
            return $m[1].'/'.ltrim($filename, '/');
        }

        throw new RuntimeException(sprintf('Unable to resolve sibling URL for "%s".', $pokechillSourceUrl));
    }
}

<?php

declare(strict_types=1);

namespace App\ReferenceData\Import;

use RuntimeException;

/**
 * Fetches the upstream Pokechill JS source as a raw string.
 *
 * Important: this must not execute any JS upstream.
 */
final class PokechillSourceFetcher
{
    /**
     * @throws RuntimeException when the source can't be read.
     */
    public function fetch(string $source): string
    {
        $source = trim($source);

        if ($source === '') {
            throw new RuntimeException('Pokechill source is empty.');
        }

        $isUrl = (bool) filter_var($source, FILTER_VALIDATE_URL);
        if ($isUrl) {
            return $this->fetchFromUrl($source);
        }

        return $this->fetchFromLocalFile($source);
    }

    private function fetchFromUrl(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'header' => "User-Agent: pokechill-companion/1.0\r\n",
            ],
            'ssl' => [
                'timeout' => 30,
            ],
        ]);

        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            throw new RuntimeException(sprintf('Unable to download Pokechill source from URL: %s', $url));
        }

        return $content;
    }

    private function fetchFromLocalFile(string $path): string
    {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        if (!file_exists($normalized) || !is_file($normalized)) {
            throw new RuntimeException(sprintf('Pokechill source file not found: %s', $path));
        }

        $content = @file_get_contents($normalized);
        if ($content === false) {
            throw new RuntimeException(sprintf('Unable to read Pokechill source file: %s', $path));
        }

        return $content;
    }
}


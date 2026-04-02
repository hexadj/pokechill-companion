<?php

declare(strict_types=1);

namespace App\Recommendation\Exception;

use InvalidArgumentException;
use RuntimeException;

final class OpponentPokemonNotFoundException extends RuntimeException
{
    public static function forSourceKey(string $sourceKey): self
    {
        return new self(sprintf('No active Pokemon found for opponent source key "%s".', $sourceKey));
    }

    /**
     * @param string[] $sourceKeys
     */
    public static function forSourceKeys(array $sourceKeys): self
    {
        if ($sourceKeys === []) {
            throw new InvalidArgumentException('sourceKeys must not be empty.');
        }

        return new self(sprintf(
            'No active Pokemon found for opponent source keys: %s.',
            implode(', ', $sourceKeys),
        ));
    }
}

<?php

declare(strict_types=1);

namespace App\Error;

use RuntimeException;

/**
 * API request failed validation (HTTP 422).
 */
final class ApiRequestValidationException extends RuntimeException
{
    /**
     * @param array<string, mixed> $errors
     */
    public function __construct(
        string $message,
        private readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public static function unknownJsonKeys(array $keys): self
    {
        return new self(
            'Request contains unknown properties.',
            ['body' => [sprintf('Unknown keys: %s.', implode(', ', $keys))]],
        );
    }

    public static function invalidLimitQuery(): self
    {
        return new self(
            'Invalid limit query parameter.',
            ['limit' => ['limit must be a positive integer between 1 and 100.']],
        );
    }

    public static function invalidSearchQuery(): self
    {
        return new self(
            'Invalid search query parameter.',
            ['search' => ['search must be a string.']],
        );
    }

    /**
     * @param array<string, mixed> $errors
     */
    public static function recommendationPayloadInvalid(string $detail, array $errors = []): self
    {
        return new self($detail, $errors);
    }
}

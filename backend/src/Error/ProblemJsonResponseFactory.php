<?php

declare(strict_types=1);

namespace App\Error;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * RFC 7807-style problem responses with Content-Type application/problem+json.
 */
final class ProblemJsonResponseFactory
{
    public const TYPE_BAD_REQUEST = '/errors/bad-request';

    public const TYPE_VALIDATION = '/errors/validation';

    public const TYPE_SERVER_ERROR = '/errors/server-error';

    /**
     * @param array<string, mixed>|null $errors
     */
    public static function create(
        int $status,
        string $title,
        string $detail,
        ?string $type = null,
        ?array $errors = null,
        array $headers = [],
    ): JsonResponse {
        $body = [
            'type' => $type ?? self::defaultTypeForStatus($status),
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
        ];
        if ($errors !== null && $errors !== []) {
            $body['errors'] = $errors;
        }

        return new JsonResponse($body, $status, array_merge($headers, [
            'Content-Type' => 'application/problem+json',
        ]));
    }

    private static function defaultTypeForStatus(int $status): string
    {
        return match (true) {
            $status === 400 => self::TYPE_BAD_REQUEST,
            $status === 422 => self::TYPE_VALIDATION,
            default => self::TYPE_SERVER_ERROR,
        };
    }
}

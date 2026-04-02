<?php

declare(strict_types=1);

namespace App\Error;

use RuntimeException;

/**
 * Malformed JSON request body (HTTP 400).
 */
final class InvalidJsonBodyException extends RuntimeException
{
}

<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Assert;

use Waffle\Commons\Utils\Exception\ValidationException;

use function file_exists;
use function is_readable;
use function is_writable;
use function sprintf;
use function str_contains;
use function trim;

/**
 * Filesystem-family assertions for {@see \Waffle\Commons\Utils\Assert}
 * (Strategy A — vertical composition).
 *
 * Security: every path is first screened for the **null-byte injection** vector
 * (`\0`, used to truncate paths past a forged extension) and for emptiness
 * before any filesystem call is made. The methods read external filesystem
 * state only — they keep no in-memory state across calls.
 */
trait FileAssertionsTrait
{
    /**
     * Assert the path points at an existing file or directory; returns the path.
     *
     * @throws ValidationException When the path is unsafe/blank or does not exist.
     */
    public static function exists(string $path, ?string $message = null): string
    {
        $safe = self::guardPath($path);
        if (!file_exists($safe)) {
            throw new ValidationException($message ?? sprintf('Path "%s" does not exist.', $path));
        }

        return $safe;
    }

    /**
     * Assert the path is readable; returns the path.
     *
     * @throws ValidationException When the path is unsafe/blank or not readable.
     */
    public static function readable(string $path, ?string $message = null): string
    {
        $safe = self::guardPath($path);
        if (!is_readable($safe)) {
            throw new ValidationException($message ?? sprintf('Path "%s" is not readable.', $path));
        }

        return $safe;
    }

    /**
     * Assert the path is writable; returns the path.
     *
     * @throws ValidationException When the path is unsafe/blank or not writable.
     */
    public static function writable(string $path, ?string $message = null): string
    {
        $safe = self::guardPath($path);
        if (!is_writable($safe)) {
            throw new ValidationException($message ?? sprintf('Path "%s" is not writable.', $path));
        }

        return $safe;
    }

    /**
     * Reject null-byte injection and blank paths before any filesystem call.
     *
     * @throws ValidationException When the path carries a null byte or is blank.
     */
    private static function guardPath(string $path): string
    {
        if (str_contains($path, "\0")) {
            throw new ValidationException('Path contains an illegal null byte.');
        }

        $trimmed = trim($path);
        if ($trimmed === '') {
            throw new ValidationException('Path must not be empty.');
        }

        return $trimmed;
    }
}

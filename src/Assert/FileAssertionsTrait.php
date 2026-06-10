<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Assert;

use Waffle\Commons\Utils\Exception\ValidationException;

use function array_pop;
use function explode;
use function file_exists;
use function implode;
use function is_readable;
use function is_writable;
use function realpath;
use function sprintf;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function trim;

/**
 * Filesystem-family assertions for {@see \Waffle\Commons\Utils\Assert}
 * (Strategy A — vertical composition).
 *
 * Security: every path is first screened for the **null-byte injection** vector
 * (`\0`, used to truncate paths past a forged extension) and for emptiness
 * before any filesystem call is made. {@see self::safePath()} and
 * {@see self::within()} add **directory-traversal** rejection (`../`, `..\`)
 * so user-supplied fragments cannot climb out of an intended location
 * (SEC-05). The methods read external filesystem state only — they keep no
 * in-memory state across calls.
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
     * Reject directory-traversal sequences before any filesystem use; returns
     * the trimmed path. Any `..` segment under either separator style (`../`,
     * `..\`, `a/../b`, a bare `..`) is refused, on top of the inherited
     * null-byte and blank guards. Does NOT touch the filesystem — apply it to
     * a user-supplied path fragment before composing a storage destination
     * (SEC-05 developer guardrail).
     *
     * @throws ValidationException When the path is unsafe/blank or carries a
     *         traversal segment.
     */
    public static function safePath(string $path, ?string $message = null): string
    {
        $safe = self::guardPath($path);

        foreach (explode('/', str_replace('\\', '/', $safe)) as $segment) {
            if ($segment === '..') {
                throw new ValidationException(
                    $message ?? sprintf('Path "%s" contains an illegal directory-traversal segment.', $path),
                );
            }
        }

        return $safe;
    }

    /**
     * Assert that `$path`, resolved against the existing `$base` directory,
     * stays inside it; returns the lexically-normalized absolute target.
     *
     * The target itself need NOT exist yet (e.g. an upload destination), so
     * containment is computed lexically against `realpath($base)` rather than
     * by `realpath()`-ing the target. An absolute `$path`, or one that climbs
     * out with `..`, is rejected — defeating path-traversal writes even when
     * the caller forwards attacker-influenced metadata.
     *
     * @throws ValidationException When `$base` does not exist, or the resolved
     *         path escapes it.
     */
    public static function within(string $base, string $path, ?string $message = null): string
    {
        $safeBase = self::guardPath($base);
        $safePath = self::guardPath($path);

        $realBase = realpath($safeBase);
        if ($realBase === false) {
            throw new ValidationException($message ?? sprintf('Base directory "%s" does not exist.', $base));
        }

        $candidate = str_starts_with($safePath, '/') ? $safePath : $realBase . '/' . $safePath;
        $normalized = self::lexicalNormalize($candidate);

        if ($normalized !== $realBase && !str_starts_with($normalized, $realBase . '/')) {
            throw new ValidationException(
                $message ?? sprintf('Path "%s" escapes the permitted base directory.', $path),
            );
        }

        return $normalized;
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

    /**
     * Lexically resolve `.`/`..`/empty segments of an absolute POSIX path
     * WITHOUT touching the filesystem. A `..` never climbs above the root.
     */
    private static function lexicalNormalize(string $path): string
    {
        /** @var list<string> $stack */
        $stack = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($stack);
                continue;
            }
            $stack[] = $segment;
        }

        return '/' . implode('/', $stack);
    }
}

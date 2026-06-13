<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Assert;

use Waffle\Commons\Utils\Exception\ValidationException;

use function filter_var;
use function mb_strlen;
use function mb_trim;
use function preg_match;
use function sprintf;
use function strtolower;

use const FILTER_VALIDATE_EMAIL;

/**
 * String-family assertions for the {@see \Waffle\Commons\Utils\Assert} entry
 * point (Strategy A — vertical composition).
 *
 * Every method validates AND returns the normalized value, so it slots directly
 * into a PHP 8.5 property-hook short-setter
 * (`set => Assert::email($value)`). All methods are pure, deterministic, and
 * static — no instance or static state survives a call.
 */
trait StringAssertionsTrait
{
    /**
     * Validate an RFC-compliant email; returns it trimmed and lower-cased.
     *
     * @throws ValidationException When the value is not a valid email address.
     */
    public static function email(string $value, ?string $message = null): string
    {
        $trimmed = mb_trim($value);
        if (filter_var($trimmed, FILTER_VALIDATE_EMAIL) === false) {
            throw new ValidationException($message ?? sprintf('"%s" is not a valid email address.', $value));
        }

        return strtolower($trimmed);
    }

    /**
     * Strictly validate a UUID v4 or v5; returns it trimmed and lower-cased.
     *
     * @throws ValidationException When the value is not a v4/v5 UUID.
     */
    public static function uuid(string $value, ?string $message = null): string
    {
        $trimmed = mb_trim($value);
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[45][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        if (preg_match($pattern, $trimmed) !== 1) {
            throw new ValidationException($message ?? sprintf('"%s" is not a valid v4/v5 UUID.', $value));
        }

        return strtolower($trimmed);
    }

    /**
     * Assert an inclusive, UTF-8-safe length window; returns the value unchanged.
     *
     * @throws ValidationException When the length is outside [$min, $max].
     */
    public static function length(string $value, int $min, int $max, ?string $message = null): string
    {
        $length = mb_strlen($value);
        if ($length < $min || $length > $max) {
            throw new ValidationException(
                $message ?? sprintf('Value length %d is outside the allowed range [%d, %d].', $length, $min, $max),
            );
        }

        return $value;
    }

    /**
     * Validate against a caller-supplied PCRE pattern; returns the value unchanged.
     *
     * @throws ValidationException When the value does not match, or the pattern
     *         itself is invalid (`preg_match` reports an error).
     */
    public static function regex(string $value, string $pattern, ?string $message = null): string
    {
        if (preg_match($pattern, $value) !== 1) {
            throw new ValidationException(
                $message ?? sprintf('Value does not match the required pattern "%s".', $pattern),
            );
        }

        return $value;
    }

    /**
     * Assert a non-blank string; returns it trimmed.
     *
     * @throws ValidationException When the value is empty after trimming.
     */
    public static function notEmpty(string $value, ?string $message = null): string
    {
        $trimmed = mb_trim($value);
        if ($trimmed === '') {
            throw new ValidationException($message ?? 'Value must not be empty.');
        }

        return $trimmed;
    }
}

<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Assert;

use Waffle\Commons\Utils\Exception\ValidationException;

use function sprintf;

/**
 * Numeric-family assertions for {@see \Waffle\Commons\Utils\Assert}
 * (Strategy A — vertical composition).
 *
 * Numbers are validated, not transformed, so each method returns the value
 * unchanged. All methods are pure, deterministic, and static — no state
 * survives a call.
 */
trait NumericAssertionsTrait
{
    /**
     * @throws ValidationException When `$value` is below `$threshold`.
     */
    public static function greaterThanOrEqual(
        int|float $value,
        int|float $threshold,
        ?string $message = null,
    ): int|float {
        if ($value < $threshold) {
            throw new ValidationException($message ?? sprintf('Value %s must be >= %s.', $value, $threshold));
        }

        return $value;
    }

    /**
     * @throws ValidationException When `$value` is above `$threshold`.
     */
    public static function lessThanOrEqual(int|float $value, int|float $threshold, ?string $message = null): int|float
    {
        if ($value > $threshold) {
            throw new ValidationException($message ?? sprintf('Value %s must be <= %s.', $value, $threshold));
        }

        return $value;
    }

    /**
     * Assert an inclusive numeric window.
     *
     * @throws ValidationException When `$value` is outside [$min, $max].
     */
    public static function range(int|float $value, int|float $min, int|float $max, ?string $message = null): int|float
    {
        if ($value < $min || $value > $max) {
            throw new ValidationException(
                $message ?? sprintf('Value %s is outside the allowed range [%s, %s].', $value, $min, $max),
            );
        }

        return $value;
    }

    /**
     * Assert a strictly positive number (`> 0`).
     *
     * @throws ValidationException When `$value` is zero or negative.
     */
    public static function positive(int|float $value, ?string $message = null): int|float
    {
        if ($value <= 0) {
            throw new ValidationException($message ?? sprintf('Value %s must be strictly positive.', $value));
        }

        return $value;
    }
}

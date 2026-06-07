<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils;

use Waffle\Commons\Utils\Assert\FileAssertionsTrait;
use Waffle\Commons\Utils\Assert\NetworkAssertionsTrait;
use Waffle\Commons\Utils\Assert\NumericAssertionsTrait;
use Waffle\Commons\Utils\Assert\StringAssertionsTrait;

/**
 * Unified static entry point for Waffle assertions (Strategy A — vertical
 * composition via traits, one call site per concern family).
 *
 * Every assertion validates AND returns the sanitized/normalized value, so it
 * drops straight into a PHP 8.5 property-hook short-setter:
 *
 * ```php
 * public string $email {
 *     set => Assert::email($value);
 * }
 * ```
 *
 * The class holds no state of any kind (no instances, no static properties), so
 * it is fully safe under FrankenPHP resident-worker mode — nothing leaks across
 * request loops. It is `final readonly` and cannot be instantiated; call the
 * methods statically.
 */
final readonly class Assert
{
    use StringAssertionsTrait;
    use NumericAssertionsTrait;
    use NetworkAssertionsTrait;
    use FileAssertionsTrait;

    /**
     * Sealed: {@see Assert} is a static-only entry point and must never be
     * instantiated, so this constructor is intentionally unreachable.
     *
     * @codeCoverageIgnore
     */
    private function __construct() {}
}

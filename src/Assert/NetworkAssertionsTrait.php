<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Assert;

use Waffle\Commons\Utils\Exception\ValidationException;

use function ctype_digit;
use function filter_var;
use function sprintf;
use function strpos;
use function strtolower;
use function substr;
use function trim;

use const FILTER_FLAG_IPV4;
use const FILTER_VALIDATE_IP;

/**
 * Network-family assertions for {@see \Waffle\Commons\Utils\Assert}
 * (Strategy A — vertical composition).
 *
 * Pure, deterministic, static. Values are returned trimmed (and IPv6 hosts
 * lower-cased) so the result drops straight into a property-hook setter.
 */
trait NetworkAssertionsTrait
{
    /**
     * Validate an IPv4 or IPv6 address; returns it trimmed (lower-cased).
     *
     * @throws ValidationException When the value is not a valid IP address.
     */
    public static function ip(string $value, ?string $message = null): string
    {
        $trimmed = trim($value);
        if (filter_var($trimmed, FILTER_VALIDATE_IP) === false) {
            throw new ValidationException($message ?? sprintf('"%s" is not a valid IP address.', $value));
        }

        return strtolower($trimmed);
    }

    /**
     * Assert a TCP/UDP port strictly within the 1–65535 range.
     *
     * @throws ValidationException When the port is outside [1, 65535].
     */
    public static function port(int $value, ?string $message = null): int
    {
        if ($value < 1 || $value > 65_535) {
            throw new ValidationException($message ?? sprintf('Port %d must be between 1 and 65535.', $value));
        }

        return $value;
    }

    /**
     * Validate CIDR notation (`address/prefix`); returns it trimmed (lower-cased).
     *
     * The prefix length is bounded to the address family: 0–32 for IPv4,
     * 0–128 for IPv6.
     *
     * @throws ValidationException When the notation, address, or prefix is invalid.
     */
    public static function cidr(string $value, ?string $message = null): string
    {
        $trimmed = trim($value);
        $slash = strpos($trimmed, '/');
        if ($slash === false) {
            throw new ValidationException($message ?? sprintf('"%s" is not valid CIDR notation.', $value));
        }

        $address = substr($trimmed, 0, $slash);
        $prefixRaw = substr($trimmed, $slash + 1);
        if ($prefixRaw === '' || !ctype_digit($prefixRaw)) {
            throw new ValidationException($message ?? sprintf('"%s" is not valid CIDR notation.', $value));
        }

        $prefix = (int) $prefixRaw;
        $isIpv4 = filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
        $maxPrefix = $isIpv4 ? 32 : 128;

        if (filter_var($address, FILTER_VALIDATE_IP) === false || $prefix > $maxPrefix) {
            throw new ValidationException($message ?? sprintf('"%s" is not valid CIDR notation.', $value));
        }

        return strtolower($trimmed);
    }
}

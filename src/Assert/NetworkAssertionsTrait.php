<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Assert;

use Waffle\Commons\Utils\Exception\ValidationException;

use function ctype_digit;
use function filter_var;
use function inet_pton;
use function intdiv;
use function mb_trim;
use function ord;
use function sprintf;
use function strlen;
use function strpos;
use function strtolower;
use function substr;

use const FILTER_FLAG_IPV4;
use const FILTER_VALIDATE_IP;

/**
 * Network-family assertions for {@see \Waffle\Commons\Utils\Assert}
 * (Strategy A — vertical composition).
 *
 * Pure, deterministic, static. Values are returned trimmed (and IPv6 hosts
 * lower-cased) so the result drops straight into a property-hook setter.
 * {@see self::isPublicIp()} / {@see self::ipInCidr()} add the reusable
 * SSRF-defence primitive consumed by the HTTP client (SEC-02).
 */
trait NetworkAssertionsTrait
{
    /**
     * Loopback, private, link-local, CGNAT, multicast and otherwise-reserved
     * ranges that must never be the target of a server-side request — the
     * blocklist behind {@see self::isPublicIp()} (SEC-02 SSRF defence).
     * Covers RFC 1918, RFC 4193, RFC 6598, loopback, link-local, multicast,
     * IPv4-mapped IPv6 and the IETF documentation/benchmark ranges.
     *
     * @var list<string>
     */
    private const array PRIVATE_RESERVED_CIDRS = [
        // IPv4
        '0.0.0.0/8', // "this" network / unspecified
        '10.0.0.0/8', // RFC 1918 private
        '100.64.0.0/10', // RFC 6598 carrier-grade NAT
        '127.0.0.0/8', // loopback
        '169.254.0.0/16', // link-local
        '172.16.0.0/12', // RFC 1918 private
        '192.0.0.0/24', // IETF protocol assignments
        '192.0.2.0/24', // TEST-NET-1
        '192.168.0.0/16', // RFC 1918 private
        '198.18.0.0/15', // benchmarking
        '198.51.100.0/24', // TEST-NET-2
        '203.0.113.0/24', // TEST-NET-3
        '224.0.0.0/4', // multicast
        '240.0.0.0/4', // reserved (incl. 255.255.255.255 broadcast)
        // IPv6
        '::/128', // unspecified
        '::1/128', // loopback
        '::ffff:0:0/96', // IPv4-mapped
        '64:ff9b::/96', // NAT64
        '100::/64', // discard-only
        'fc00::/7', // RFC 4193 unique-local
        'fe80::/10', // link-local
        'ff00::/8', // multicast
    ];

    /**
     * Validate an IPv4 or IPv6 address; returns it trimmed (lower-cased).
     *
     * @throws ValidationException When the value is not a valid IP address.
     */
    public static function ip(string $value, ?string $message = null): string
    {
        $trimmed = mb_trim($value);
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
        $trimmed = mb_trim($value);
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

    /**
     * True when `$ip` is a syntactically valid address that is NOT loopback,
     * private (RFC 1918 / RFC 4193), link-local, CGNAT, multicast, or otherwise
     * reserved. **Fail-closed:** a malformed address returns `false`.
     *
     * The primary use is SSRF defence — validate a DNS-resolved address before
     * dialing it. IPv4-mapped IPv6 (`::ffff:0:0/96`) is treated as non-public
     * by design, so a mapped private address cannot slip through.
     */
    public static function isPublicIp(string $ip): bool
    {
        $trimmed = mb_trim($ip);
        if (filter_var($trimmed, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        foreach (self::PRIVATE_RESERVED_CIDRS as $cidr) {
            if (self::ipInCidr($trimmed, $cidr)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Pure, family-aware test of whether `$ip` falls inside the `$cidr` block
     * (`address/prefix`). Returns `false` for any malformed input or a family
     * mismatch (an IPv4 address is never inside an IPv6 block).
     */
    public static function ipInCidr(string $ip, string $cidr): bool
    {
        $ipTrim = mb_trim($ip);
        if (filter_var($ipTrim, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        $slash = strpos($cidr, '/');
        if ($slash === false) {
            return false;
        }

        $subnet = substr($cidr, 0, $slash);
        $prefixRaw = substr($cidr, $slash + 1);
        if ($prefixRaw === '' || !ctype_digit($prefixRaw) || filter_var($subnet, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        $ipBin = inet_pton($ipTrim);
        $subnetBin = inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $prefix = (int) $prefixRaw;
        if ($prefix > (strlen($ipBin) * 8)) {
            return false;
        }

        return self::binaryPrefixMatches($ipBin, $subnetBin, $prefix);
    }

    /**
     * Compare the leading `$bits` bits of two equal-length packed addresses.
     */
    private static function binaryPrefixMatches(string $a, string $b, int $bits): bool
    {
        $fullBytes = intdiv($bits, 8);
        if ($fullBytes > 0 && substr($a, 0, $fullBytes) !== substr($b, 0, $fullBytes)) {
            return false;
        }

        $remainingBits = $bits % 8;
        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord(substr($a, $fullBytes, 1)) & $mask) === (ord(substr($b, $fullBytes, 1)) & $mask);
    }
}

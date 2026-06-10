<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Waffle\Commons\Contracts\Exception\Validation\ValidationExceptionInterface;
use Waffle\Commons\Utils\Assert;
use Waffle\Commons\Utils\Assert\FileAssertionsTrait;
use Waffle\Commons\Utils\Assert\NetworkAssertionsTrait;
use Waffle\Commons\Utils\Assert\NumericAssertionsTrait;
use Waffle\Commons\Utils\Assert\StringAssertionsTrait;
use Waffle\Commons\Utils\Exception\ValidationException;
use WaffleTests\Commons\Utils\AbstractTestCase as TestCase;

use function is_file;
use function realpath;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

#[CoversClass(Assert::class)]
#[CoversClass(ValidationException::class)]
#[CoversTrait(StringAssertionsTrait::class)]
#[CoversTrait(NumericAssertionsTrait::class)]
#[CoversTrait(NetworkAssertionsTrait::class)]
#[CoversTrait(FileAssertionsTrait::class)]
final class AssertTest extends TestCase
{
    /** Absolute path that is guaranteed never to exist (for negative FS cases). */
    private const string MISSING_PATH = '/waffle-commons/var/__assert_missing__/nope.bin';

    private ?string $tempFile = null;

    protected function tearDown(): void
    {
        if ($this->tempFile !== null) {
            if (is_file($this->tempFile)) {
                unlink($this->tempFile);
            }

            $this->tempFile = null;
        }

        parent::tearDown();
    }

    // ---------------------------------------------------------------- String

    /**
     * @param non-empty-string $input
     * @param non-empty-string $expected
     */
    #[DataProvider('validEmailProvider')]
    public function testEmailValidatesAndNormalises(string $input, string $expected): void
    {
        static::assertSame($expected, Assert::email($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validEmailProvider(): iterable
    {
        yield 'trims and lower-cases' => ['  ADA@Example.COM ', 'ada@example.com'];
        yield 'already canonical' => ['user@host.io', 'user@host.io'];
        yield 'plus addressing' => ['Dev+Tag@Domain.DEV', 'dev+tag@domain.dev'];
    }

    public function testEmailRejectsInvalidWithDefaultMessage(): void
    {
        // The hybrid exception *is* an InvalidArgumentException (gatekeeper-literal).
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"not-an-email" is not a valid email address.');

        Assert::email('not-an-email');
    }

    public function testEmailRejectsInvalidWithCustomMessage(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Provide a work email.');

        Assert::email('  ', 'Provide a work email.');
    }

    public function testUuidValidatesV4AndLowerCases(): void
    {
        static::assertSame(
            '9b2e4c7a-1f3d-4a6b-8c2e-0f1a2b3c4d5e',
            Assert::uuid('  9B2E4C7A-1F3D-4A6B-8C2E-0F1A2B3C4D5E '),
        );
    }

    public function testUuidValidatesV5(): void
    {
        static::assertSame(
            '21f7f8de-8051-5b89-8680-0195ef798b6a',
            Assert::uuid('21f7f8de-8051-5b89-8680-0195ef798b6a'),
        );
    }

    public function testUuidRejectsMalformedValue(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('"not-a-uuid" is not a valid v4/v5 UUID.');

        Assert::uuid('not-a-uuid');
    }

    public function testLengthAcceptsInclusiveBounds(): void
    {
        static::assertSame('abc', Assert::length('abc', 3, 8));
        static::assertSame('abcdefgh', Assert::length('abcdefgh', 3, 8));
    }

    public function testLengthIsMultibyteAware(): void
    {
        // 4 code points (not 5 bytes) — proves mb_strlen, not strlen.
        static::assertSame('café', Assert::length('café', 4, 4));
    }

    public function testLengthRejectsTooShort(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Value length 2 is outside the allowed range [3, 8].');

        Assert::length('ab', 3, 8);
    }

    public function testLengthRejectsTooLong(): void
    {
        $this->expectException(ValidationException::class);

        Assert::length('this-is-far-too-long', 3, 8);
    }

    public function testRegexReturnsValueOnMatch(): void
    {
        static::assertSame('ABC-123', Assert::regex('ABC-123', '/^[A-Z]+-\d+$/'));
    }

    public function testRegexRejectsNonMatchingValue(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Value does not match the required pattern "/^\d+$/".');

        Assert::regex('not-digits', '/^\d+$/');
    }

    public function testNotEmptyReturnsTrimmedValue(): void
    {
        static::assertSame('hello', Assert::notEmpty('  hello  '));
    }

    public function testNotEmptyRejectsBlankWithDefaultMessage(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Value must not be empty.');

        Assert::notEmpty("  \t ");
    }

    public function testNotEmptyRejectsBlankWithCustomMessage(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Name is required.');

        Assert::notEmpty('', 'Name is required.');
    }

    // --------------------------------------------------------------- Numeric

    public function testGreaterThanOrEqualAcceptsBoundaryAndAbove(): void
    {
        static::assertSame(5, Assert::greaterThanOrEqual(5, 5));
        static::assertSame(9, Assert::greaterThanOrEqual(9, 5));
    }

    public function testGreaterThanOrEqualRejectsBelow(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Value 4 must be >= 5.');

        Assert::greaterThanOrEqual(4, 5);
    }

    public function testLessThanOrEqualAcceptsBoundaryAndBelow(): void
    {
        static::assertSame(5, Assert::lessThanOrEqual(5, 5));
        static::assertSame(1.5, Assert::lessThanOrEqual(1.5, 5));
    }

    public function testLessThanOrEqualRejectsAbove(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Value 6 must be <= 5.');

        Assert::lessThanOrEqual(6, 5);
    }

    public function testRangeAcceptsInclusiveBounds(): void
    {
        static::assertSame(18, Assert::range(18, 18, 130));
        static::assertSame(130, Assert::range(130, 18, 130));
        static::assertSame(42.5, Assert::range(42.5, 18, 130));
    }

    public function testRangeRejectsBelowMinimum(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Value 17 is outside the allowed range [18, 130].');

        Assert::range(17, 18, 130);
    }

    public function testRangeRejectsAboveMaximumWithCustomMessage(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Too old.');

        Assert::range(131, 18, 130, 'Too old.');
    }

    public function testPositiveAcceptsStrictlyPositive(): void
    {
        static::assertSame(1, Assert::positive(1));
        static::assertSame(0.01, Assert::positive(0.01));
    }

    public function testPositiveRejectsZero(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Value 0 must be strictly positive.');

        Assert::positive(0);
    }

    public function testPositiveRejectsNegative(): void
    {
        $this->expectException(ValidationException::class);

        Assert::positive(-3);
    }

    // --------------------------------------------------------------- Network

    public function testIpValidatesV4(): void
    {
        static::assertSame('192.168.1.1', Assert::ip('  192.168.1.1 '));
    }

    public function testIpValidatesAndLowerCasesV6(): void
    {
        static::assertSame('2001:db8::1', Assert::ip('2001:DB8::1'));
    }

    public function testIpRejectsInvalidWithCustomMessage(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Bad source address.');

        Assert::ip('999.0.0.1', 'Bad source address.');
    }

    public function testPortAcceptsBoundaries(): void
    {
        static::assertSame(1, Assert::port(1));
        static::assertSame(65_535, Assert::port(65_535));
        static::assertSame(8080, Assert::port(8080));
    }

    public function testPortRejectsBelowRange(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Port 0 must be between 1 and 65535.');

        Assert::port(0);
    }

    public function testPortRejectsAboveRange(): void
    {
        $this->expectException(ValidationException::class);

        Assert::port(65_536);
    }

    public function testCidrValidatesV4(): void
    {
        static::assertSame('192.168.0.0/24', Assert::cidr('  192.168.0.0/24 '));
    }

    public function testCidrValidatesAndLowerCasesV6(): void
    {
        static::assertSame('2001:db8::/32', Assert::cidr('2001:DB8::/32'));
    }

    public function testCidrRejectsMissingSlash(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('"192.168.0.0" is not valid CIDR notation.');

        Assert::cidr('192.168.0.0');
    }

    public function testCidrRejectsEmptyPrefix(): void
    {
        $this->expectException(ValidationException::class);

        Assert::cidr('192.168.0.0/');
    }

    public function testCidrRejectsNonNumericPrefix(): void
    {
        $this->expectException(ValidationException::class);

        Assert::cidr('192.168.0.0/ab');
    }

    public function testCidrRejectsInvalidAddress(): void
    {
        $this->expectException(ValidationException::class);

        Assert::cidr('999.1.1.1/24');
    }

    public function testCidrRejectsPrefixAboveV4Maximum(): void
    {
        $this->expectException(ValidationException::class);

        Assert::cidr('192.168.0.0/33');
    }

    public function testCidrRejectsPrefixAboveV6Maximum(): void
    {
        $this->expectException(ValidationException::class);

        Assert::cidr('2001:db8::/129');
    }

    // ------------------------------------------------------------------ File

    public function testExistsReturnsSafePathForRealFile(): void
    {
        $path = $this->makeTempFile();
        static::assertSame($path, Assert::exists($path));
    }

    public function testExistsRejectsMissingPath(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('does not exist');

        Assert::exists(self::MISSING_PATH);
    }

    public function testReadableReturnsSafePathForRealFile(): void
    {
        $path = $this->makeTempFile();
        static::assertSame($path, Assert::readable($path));
    }

    public function testReadableRejectsMissingPath(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('is not readable');

        Assert::readable(self::MISSING_PATH);
    }

    public function testWritableReturnsSafePathForRealFile(): void
    {
        $path = $this->makeTempFile();
        static::assertSame($path, Assert::writable($path));
    }

    public function testWritableRejectsMissingPathWithCustomMessage(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot write the cache file.');

        Assert::writable(self::MISSING_PATH, 'Cannot write the cache file.');
    }

    public function testFilePathRejectsNullByteInjection(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Path contains an illegal null byte.');

        Assert::exists("/etc/passwd\0.png");
    }

    public function testFilePathRejectsBlankPath(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Path must not be empty.');

        Assert::readable('   ');
    }

    // ------------------------------------------------------------- Exception

    public function testValidationExceptionIsHybridAndExposesField(): void
    {
        $withField = new ValidationException('bad', 'email');
        static::assertInstanceOf(InvalidArgumentException::class, $withField);
        static::assertInstanceOf(ValidationExceptionInterface::class, $withField);
        static::assertSame('bad', $withField->getMessage());
        static::assertSame('email', $withField->getField());
        // Defaults to the HTTP 422 status the renderer emits (ecosystem parity).
        static::assertSame(422, $withField->getCode());

        $withoutField = new ValidationException('bad');
        static::assertNull($withoutField->getField());
    }

    // ----------------------------------------------------- File: traversal

    /**
     * @param non-empty-string $path
     * @param non-empty-string $expected
     */
    #[DataProvider('safePathAcceptProvider')]
    public function testSafePathAcceptsCleanPath(string $path, string $expected): void
    {
        static::assertSame($expected, Assert::safePath($path));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function safePathAcceptProvider(): iterable
    {
        yield 'relative file' => ['uploads/avatar.png', 'uploads/avatar.png'];
        yield 'trims surrounding space' => ['  reports/q3.pdf  ', 'reports/q3.pdf'];
        yield 'dot-prefixed filename is fine' => ['data/.keep', 'data/.keep'];
        yield 'single dot segment is fine' => ['a/./b', 'a/./b'];
    }

    #[DataProvider('safePathRejectProvider')]
    public function testSafePathRejectsTraversal(string $path): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('illegal directory-traversal segment');

        Assert::safePath($path);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function safePathRejectProvider(): iterable
    {
        yield 'leading parent' => ['../etc/passwd'];
        yield 'embedded parent' => ['uploads/../../etc/passwd'];
        yield 'backslash parent' => ['uploads\\..\\secret'];
        yield 'bare parent' => ['..'];
    }

    public function testSafePathRejectsNullByte(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Path contains an illegal null byte.');

        Assert::safePath("ok/file\0.png");
    }

    public function testWithinReturnsNormalizedTargetBeneathBase(): void
    {
        $base = realpath(sys_get_temp_dir());
        static::assertIsString($base);

        static::assertSame($base . '/uploads/avatar.png', Assert::within($base, 'uploads/avatar.png'));
        // '.'/'..' collapse lexically but still land inside the base.
        static::assertSame($base . '/a/b', Assert::within($base, 'a/./c/../b'));
    }

    public function testWithinRejectsEscapingPath(): void
    {
        $base = realpath(sys_get_temp_dir());
        static::assertIsString($base);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('escapes the permitted base directory');

        Assert::within($base, '../../etc/passwd');
    }

    public function testWithinRejectsAbsoluteEscape(): void
    {
        $base = realpath(sys_get_temp_dir());
        static::assertIsString($base);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('escapes the permitted base directory');

        Assert::within($base, '/etc/passwd');
    }

    public function testWithinRejectsMissingBase(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('does not exist');

        Assert::within(self::MISSING_PATH, 'file.txt');
    }

    // ------------------------------------------------------- Network: SSRF

    #[DataProvider('publicIpProvider')]
    public function testIsPublicIp(string $ip, bool $expected): void
    {
        static::assertSame($expected, Assert::isPublicIp($ip));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function publicIpProvider(): iterable
    {
        yield 'public v4 (Google)' => ['8.8.8.8', true];
        yield 'public v4 (Cloudflare)' => ['1.1.1.1', true];
        yield 'public v6 (Cloudflare)' => ['2606:4700:4700::1111', true];
        yield 'rfc1918 10/8' => ['10.0.0.1', false];
        yield 'rfc1918 172.16/12' => ['172.16.5.4', false];
        yield 'rfc1918 192.168/16' => ['192.168.1.1', false];
        yield 'loopback v4' => ['127.0.0.1', false];
        yield 'link-local v4' => ['169.254.10.1', false];
        yield 'cgnat 100.64/10' => ['100.64.0.1', false];
        yield 'unspecified v4' => ['0.0.0.0', false];
        yield 'multicast v4' => ['224.0.0.1', false];
        yield 'broadcast v4' => ['255.255.255.255', false];
        yield 'loopback v6' => ['::1', false];
        yield 'unique-local v6' => ['fc00::1', false];
        yield 'link-local v6' => ['fe80::1', false];
        yield 'multicast v6' => ['ff02::1', false];
        yield 'ipv4-mapped loopback' => ['::ffff:127.0.0.1', false];
        yield 'malformed' => ['not-an-ip', false];
        yield 'out-of-range octet' => ['999.1.1.1', false];
        yield 'empty' => ['', false];
    }

    #[DataProvider('cidrContainmentProvider')]
    public function testIpInCidr(string $ip, string $cidr, bool $expected): void
    {
        static::assertSame($expected, Assert::ipInCidr($ip, $cidr));
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function cidrContainmentProvider(): iterable
    {
        yield 'v4 inside /8' => ['10.1.2.3', '10.0.0.0/8', true];
        yield 'v4 inside /16' => ['192.168.5.4', '192.168.0.0/16', true];
        yield 'v4 outside /8' => ['11.0.0.1', '10.0.0.0/8', false];
        yield 'v4 match-all /0' => ['8.8.8.8', '0.0.0.0/0', true];
        yield 'v6 inside /32' => ['2001:db8::5', '2001:db8::/32', true];
        yield 'family mismatch' => ['10.0.0.1', '2001:db8::/32', false];
        yield 'bad cidr (no slash)' => ['10.0.0.1', '10.0.0.0', false];
        yield 'bad cidr (empty prefix)' => ['10.0.0.1', '10.0.0.0/', false];
        yield 'bad cidr (non-numeric)' => ['10.0.0.1', '10.0.0.0/ab', false];
        yield 'prefix above v4 max' => ['10.0.0.1', '10.0.0.0/33', false];
        yield 'invalid ip' => ['nope', '10.0.0.0/8', false];
        yield 'invalid subnet' => ['10.0.0.1', '999.0.0.0/8', false];
    }

    /**
     * Create a real, self-cleaning temp file (exists + readable + writable as the
     * container user) for the happy-path filesystem assertions.
     */
    private function makeTempFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'wfl_assert_');
        static::assertIsString($path);
        $this->tempFile = $path;

        return $path;
    }
}

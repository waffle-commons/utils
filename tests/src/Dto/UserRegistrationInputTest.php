<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Dto;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Commons\Utils\Dto\UserRegistrationInput;
use Waffle\Commons\Utils\Exception\ValidationException;
use WaffleTests\Commons\Utils\AbstractTestCase as TestCase;

/**
 * Proves the property-hook ergonomics: assigning a constructor argument runs the
 * {@see \Waffle\Commons\Utils\Assert} short-setter, so the stored value is both
 * validated and cleansed in a single line per property.
 */
#[CoversClass(UserRegistrationInput::class)]
final class UserRegistrationInputTest extends TestCase
{
    public function testConstructorValidatesAndCleansesEveryField(): void
    {
        $input = new UserRegistrationInput(
            email: '  ADA@Example.COM ',
            username: '  ada_lovelace  ',
            age: 36,
            signupIp: '2001:DB8::1',
        );

        // email: trimmed + lower-cased by Assert::email through the hook.
        static::assertSame('ada@example.com', $input->email);
        // username: trimmed by Assert::notEmpty before the length check, so the
        // surrounding whitespace is stripped from the stored value.
        static::assertSame('ada_lovelace', $input->username);
        static::assertSame(36, $input->age);
        // signupIp: trimmed + lower-cased by Assert::ip.
        static::assertSame('2001:db8::1', $input->signupIp);
    }

    public function testInvalidEmailIsRejectedAtConstruction(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('is not a valid email address');

        new UserRegistrationInput(email: 'not-an-email', username: 'valid_name', age: 30, signupIp: '10.0.0.1');
    }

    public function testUnderageRegistrantIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('is outside the allowed range [18, 130]');

        new UserRegistrationInput(email: 'kid@example.com', username: 'valid_name', age: 17, signupIp: '10.0.0.1');
    }

    public function testBlankUsernameIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Value must not be empty.');

        new UserRegistrationInput(email: 'user@example.com', username: '   ', age: 30, signupIp: '10.0.0.1');
    }

    public function testInvalidSignupIpIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        new UserRegistrationInput(email: 'user@example.com', username: 'valid_name', age: 30, signupIp: 'not-an-ip');
    }
}

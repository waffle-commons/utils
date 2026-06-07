<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Dto;

use Waffle\Commons\Utils\Assert;

/**
 * Demonstration DTO for the {@see Assert} property-hook ergonomics (RFC-011
 * data integrity + RFC on assertions).
 *
 * Each property is a `public private(set)` hook whose short-setter delegates to
 * an {@see Assert} method: the value is validated AND cleansed in one line, and
 * the cleansed result is what gets stored. Assigning the constructor arguments
 * therefore guarantees the instance is valid and normalized — e.g. an email of
 * `'  ADA@Example.COM '` is stored as `'ada@example.com'`.
 *
 * Hooked properties cannot be `readonly`, so the class is `final` with
 * write-once `private(set)` visibility rather than `final readonly`.
 */
final class UserRegistrationInput
{
    /** Trimmed + lower-cased, RFC-validated email. */
    public private(set) string $email {
        set => Assert::email($value);
    }

    /** 3–32 character (UTF-8) handle, validated non-empty. */
    public private(set) string $username {
        set => Assert::length(Assert::notEmpty($value), 3, 32);
    }

    /** Registrant age, inclusive 18–130. */
    public private(set) int $age {
        set => Assert::range($value, 18, 130);
    }

    /** Captured sign-up IP (IPv4/IPv6), trimmed + lower-cased. */
    public private(set) string $signupIp {
        set => Assert::ip($value);
    }

    public function __construct(string $email, string $username, int $age, string $signupIp)
    {
        $this->email = $email;
        $this->username = $username;
        $this->age = $age;
        $this->signupIp = $signupIp;
    }
}

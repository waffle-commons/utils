<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Dto\Shape\Fixture;

use SensitiveParameter;
use Waffle\Commons\Contracts\Attribute\Dto;

/**
 * Hooks and visibility: what goes in is not what comes out.
 */
#[Dto]
final class Customer
{
    /** Static: not instance data, never an output. */
    public static string $schema = 'v1';

    /** Assigned in the constructor body, through a short `set` hook. */
    public private(set) string $email {
        set => mb_strtolower($value);
    }

    /** Fed by the `password` parameter under another name. */
    public private(set) string $passwordHash;

    /** Computed: a virtual property with a `get` hook. */
    public string $display {
        get => $this->nickname ?? $this->email;
    }

    /** Write-only: a virtual property with only a `set` hook. */
    public string $alias {
        set(string $value) {
            $this->nickname = $value;
        }
    }

    public function __construct(
        public readonly string $id,
        string $email,
        #[SensitiveParameter]
        string $password,
        public protected(set) ?string $nickname = null,
    ) {
        $this->email = $email;
        $this->passwordHash = hash('sha256', $password);
    }
}

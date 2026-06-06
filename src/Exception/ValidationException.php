<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Exception;

use InvalidArgumentException;
use Waffle\Commons\Contracts\Exception\Validation\ValidationExceptionInterface;

/**
 * The failure raised by every {@see \Waffle\Commons\Utils\Assert} assertion.
 *
 * It extends the SPL `InvalidArgumentException` (so a caller may catch the
 * familiar argument-contract failure) **and** implements the framework's
 * {@see ValidationExceptionInterface}, which the JsonErrorRenderer recognises
 * as an RFC 7807 HTTP 422 response and which the data hydrator treats as a
 * poisoned-record rejection. One exception type therefore satisfies both the
 * plain-PHP and the Waffle domain contracts.
 *
 * The exception code defaults to `422` — the HTTP status the renderer emits —
 * matching `Waffle\Exception\ValidationException` so both validation failures
 * report an identical code across the ecosystem.
 */
final class ValidationException extends InvalidArgumentException implements ValidationExceptionInterface
{
    public function __construct(
        string $message,
        private readonly ?string $field = null,
    ) {
        parent::__construct($message, 422);
    }

    /**
     * The offending property name, or null when the failure is not tied to a
     * single field.
     */
    #[\Override]
    public function getField(): ?string
    {
        return $this->field;
    }
}

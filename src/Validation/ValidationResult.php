<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Validation;

use Waffle\Commons\Contracts\Validation\ValidationResultInterface;
use Waffle\Commons\Contracts\Validation\ViolationInterface;

/**
 * Immutable outcome of a {@see AssertValidator::validate()} call: valid when the
 * violation list is empty.
 *
 * @see ValidationResultInterface
 */
final readonly class ValidationResult implements ValidationResultInterface
{
    /**
     * @param list<ViolationInterface> $violations
     */
    public function __construct(
        private array $violations = [],
    ) {}

    #[\Override]
    public function isValid(): bool
    {
        return $this->violations === [];
    }

    /**
     * @return list<ViolationInterface>
     */
    #[\Override]
    public function getViolations(): array
    {
        return $this->violations;
    }
}

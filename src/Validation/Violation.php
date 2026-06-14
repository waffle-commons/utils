<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Validation;

use Waffle\Commons\Contracts\Validation\ViolationInterface;

/**
 * Immutable single validation failure produced by {@see AssertValidator}.
 *
 * @see ViolationInterface
 */
final readonly class Violation implements ViolationInterface
{
    public function __construct(
        private string $message,
        private string $propertyPath,
        private mixed $invalidValue,
    ) {}

    #[\Override]
    public function getMessage(): string
    {
        return $this->message;
    }

    #[\Override]
    public function getPropertyPath(): string
    {
        return $this->propertyPath;
    }

    #[\Override]
    public function getInvalidValue(): mixed
    {
        return $this->invalidValue;
    }
}

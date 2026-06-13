<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Validation;

use Waffle\Commons\Contracts\Exception\Validation\ValidationExceptionInterface;
use Waffle\Commons\Contracts\Validation\SelfValidatingInterface;
use Waffle\Commons\Contracts\Validation\ValidationResultInterface;
use Waffle\Commons\Contracts\Validation\ValidatorInterface;

/**
 * Injectable, mockable validator (DX-05) wrapping the static
 * {@see \Waffle\Commons\Utils\Assert} facade.
 *
 * Userland services type-hint {@see ValidatorInterface} instead of calling
 * `Assert` statically, so validation can be mocked in tests. An object opts into
 * validation by implementing {@see SelfValidatingInterface}; this validator
 * invokes its assertions and converts the first thrown
 * {@see ValidationExceptionInterface} into a {@see ValidationResult}. Objects
 * that do not implement the contract carry no rules to check and are reported as
 * valid.
 *
 * Holds no state — safe under FrankenPHP resident-worker mode.
 */
final class AssertValidator implements ValidatorInterface
{
    /**
     * The `$groups` argument is reserved for group-filtered validation; the
     * Assert-based validator currently runs every rule the object declares.
     */
    #[\Override]
    public function validate(object $value, array $groups = []): ValidationResultInterface
    {
        if (!$value instanceof SelfValidatingInterface) {
            return new ValidationResult();
        }

        try {
            $value->assertValid();
        } catch (ValidationExceptionInterface $exception) {
            return new ValidationResult([
                new Violation($exception->getMessage(), $exception->getField() ?? '', $value),
            ]);
        }

        return new ValidationResult();
    }
}

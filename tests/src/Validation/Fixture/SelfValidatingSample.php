<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Validation\Fixture;

use Waffle\Commons\Contracts\Validation\SelfValidatingInterface;
use Waffle\Commons\Utils\Assert;
use Waffle\Commons\Utils\Exception\ValidationException;

/**
 * Test double opting into validation via {@see SelfValidatingInterface}.
 *
 * When `$fieldError` is set it throws a field-scoped {@see ValidationException}
 * (exercising the propertyPath mapping); otherwise the email runs through the
 * `Assert` facade, whose failures carry no field and map to an empty path.
 */
final readonly class SelfValidatingSample implements SelfValidatingInterface
{
    public function __construct(
        private string $email = 'ok@example.com',
        private ?string $fieldError = null,
    ) {}

    #[\Override]
    public function assertValid(): void
    {
        if ($this->fieldError !== null) {
            throw new ValidationException('Field-scoped failure.', $this->fieldError);
        }

        Assert::email($this->email);
    }
}

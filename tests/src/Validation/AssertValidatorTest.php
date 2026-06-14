<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Waffle\Commons\Contracts\Validation\ValidatorInterface;
use Waffle\Commons\Utils\Validation\AssertValidator;
use Waffle\Commons\Utils\Validation\ValidationResult;
use Waffle\Commons\Utils\Validation\Violation;
use WaffleTests\Commons\Utils\AbstractTestCase as TestCase;
use WaffleTests\Commons\Utils\Validation\Fixture\SelfValidatingSample;

#[CoversClass(AssertValidator::class)]
#[CoversClass(ValidationResult::class)]
#[CoversClass(Violation::class)]
final class AssertValidatorTest extends TestCase
{
    public function testValidSelfValidatingObjectHasNoViolations(): void
    {
        $result = new AssertValidator()->validate(new SelfValidatingSample('good@example.com'));

        static::assertTrue($result->isValid());
        static::assertSame([], $result->getViolations());
    }

    public function testAssertFailureMapsToViolationWithEmptyPropertyPath(): void
    {
        $sample = new SelfValidatingSample('not-an-email');

        $result = new AssertValidator()->validate($sample);

        static::assertFalse($result->isValid());
        $violations = $result->getViolations();
        static::assertCount(1, $violations);
        static::assertStringContainsString('not a valid email address', $violations[0]->getMessage());
        static::assertSame('', $violations[0]->getPropertyPath());
        static::assertSame($sample, $violations[0]->getInvalidValue());
    }

    public function testFieldScopedFailureMapsToPropertyPath(): void
    {
        $result = new AssertValidator()->validate(new SelfValidatingSample(fieldError: 'email'));

        static::assertFalse($result->isValid());
        $violations = $result->getViolations();
        static::assertCount(1, $violations);
        static::assertSame('email', $violations[0]->getPropertyPath());
        static::assertSame('Field-scoped failure.', $violations[0]->getMessage());
    }

    public function testNonSelfValidatingObjectIsConsideredValid(): void
    {
        $result = new AssertValidator()->validate(new stdClass());

        static::assertTrue($result->isValid());
        static::assertSame([], $result->getViolations());
    }

    public function testInterfaceIsMockableForUserlandTests(): void
    {
        $canned = new ValidationResult();
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects(self::once())->method('validate')->willReturn($canned);

        static::assertSame($canned, $validator->validate(new stdClass()));
    }
}

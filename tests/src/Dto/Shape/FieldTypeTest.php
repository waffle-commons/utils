<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Dto\Shape;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Waffle\Commons\Utils\Dto\Shape\Enum\TypeKind;
use Waffle\Commons\Utils\Dto\Shape\FieldType;
use WaffleTests\Commons\Utils\AbstractTestCase as TestCase;
use WaffleTests\Commons\Utils\Dto\Shape\Fixture\Address;
use WaffleTests\Commons\Utils\Dto\Shape\Fixture\Status;

#[CoversClass(FieldType::class)]
#[CoversClass(TypeKind::class)]
final class FieldTypeTest extends TestCase
{
    /**
     * @return iterable<string, array{FieldType, string, string|null}>
     */
    public static function types(): iterable
    {
        yield 'int' => [FieldType::scalar(TypeKind::Int), 'int', null];
        yield 'nullable float' => [FieldType::scalar(TypeKind::Float, true), '?float', null];
        yield 'string' => [FieldType::scalar(TypeKind::String), 'string', null];
        yield 'bool' => [FieldType::scalar(TypeKind::Bool), 'bool', null];
        yield 'enum' => [FieldType::enum(Status::class, TypeKind::String, true), '?' . Status::class, null];
        yield 'dto' => [FieldType::dto(Address::class), Address::class, Address::class];
        yield 'list of dto' => [
            FieldType::listOf(FieldType::dto(Address::class), true),
            '?list<' . Address::class . '>',
            Address::class,
        ];
        yield 'list of int' => [FieldType::listOf(FieldType::scalar(TypeKind::Int)), 'list<int>', null];
    }

    #[DataProvider('types')]
    public function testDescribesTheTypeAndTheDtoItRefersTo(FieldType $type, string $described, ?string $dto): void
    {
        static::assertSame($described, $type->describe());
        static::assertSame($dto, $type->referencedDto());
    }

    public function testAnEnumCarriesItsClassAndItsBackingKind(): void
    {
        $type = FieldType::enum(Status::class, TypeKind::Int);

        static::assertSame(TypeKind::Enum, $type->kind);
        static::assertSame(Status::class, $type->class);
        static::assertSame(TypeKind::Int, $type->backing);
        static::assertFalse($type->nullable);
        static::assertNull($type->item);
    }

    public function testAScalarFactoryRejectsANonScalarKind(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"dto" is not a scalar kind.');

        FieldType::scalar(TypeKind::Dto);
    }

    public function testAnEnumIsBackedByIntOrString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('An enum is backed by int or string, not "float".');

        FieldType::enum(Status::class, TypeKind::Float);
    }

    public function testAListElementIsNeverNullable(): void
    {
        $this->expectException(InvalidArgumentException::class);

        FieldType::listOf(FieldType::scalar(TypeKind::Int, true));
    }

    public function testAListElementIsNeverAList(): void
    {
        $this->expectException(InvalidArgumentException::class);

        FieldType::listOf(FieldType::listOf(FieldType::scalar(TypeKind::Int)));
    }

    /**
     * @return iterable<string, array{TypeKind, bool}>
     */
    public static function kinds(): iterable
    {
        foreach (TypeKind::cases() as $kind) {
            yield $kind->value => [$kind, in_array($kind->value, ['int', 'float', 'string', 'bool'], true)];
        }
    }

    #[DataProvider('kinds')]
    public function testOnlyTheFourJsonScalarsAreScalarKinds(TypeKind $kind, bool $scalar): void
    {
        static::assertSame($scalar, $kind->isScalar());
    }
}

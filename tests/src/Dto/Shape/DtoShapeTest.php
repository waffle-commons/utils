<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Dto\Shape;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Commons\Utils\Dto\Shape\DtoShape;
use Waffle\Commons\Utils\Dto\Shape\Enum\TypeKind;
use Waffle\Commons\Utils\Dto\Shape\FieldType;
use Waffle\Commons\Utils\Dto\Shape\InputField;
use Waffle\Commons\Utils\Dto\Shape\OutputField;
use WaffleTests\Commons\Utils\AbstractTestCase as TestCase;
use WaffleTests\Commons\Utils\Dto\Shape\Fixture\Address;
use WaffleTests\Commons\Utils\Dto\Shape\Fixture\Order;
use WaffleTests\Commons\Utils\Dto\Shape\Fixture\OrderLine;
use WaffleTests\Commons\Utils\Dto\Shape\Fixture\TreeNode;

#[CoversClass(DtoShape::class)]
#[CoversClass(InputField::class)]
#[CoversClass(OutputField::class)]
final class DtoShapeTest extends TestCase
{
    public function testFindsAFieldByNameOnEachSide(): void
    {
        $id = new InputField('id', FieldType::scalar(TypeKind::Int));
        $total = new OutputField('total', FieldType::scalar(TypeKind::Float), true);
        $shape = new DtoShape(Order::class, [$id], [$total]);

        static::assertSame($id, $shape->input('id'));
        static::assertNull($shape->input('total'));
        static::assertSame($total, $shape->output('total'));
        static::assertNull($shape->output('id'));
    }

    public function testListsEveryReferencedDtoOnceInFirstSeenOrder(): void
    {
        $shape = new DtoShape(
            Order::class,
            [
                new InputField('lines', FieldType::listOf(FieldType::dto(OrderLine::class))),
                new InputField('shipping', FieldType::dto(Address::class, true), true),
                new InputField('count', FieldType::scalar(TypeKind::Int)),
            ],
            [
                new OutputField('billing', FieldType::dto(Address::class)),
                new OutputField('root', FieldType::dto(TreeNode::class)),
            ],
        );

        static::assertSame([OrderLine::class, Address::class, TreeNode::class], $shape->dependencies());
    }

    public function testOnlyAParameterWithNeitherDefaultNorNullIsRequired(): void
    {
        $int = FieldType::scalar(TypeKind::Int);

        static::assertTrue(new InputField('a', $int)->required);
        static::assertFalse(new InputField('b', $int, true, 3)->required);
        static::assertFalse(new InputField('c', FieldType::scalar(TypeKind::Int, true))->required);
    }
}

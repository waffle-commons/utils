<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Dto\Shape;

use ArrayIterator;
use BackedEnum;
use Countable;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Traversable;
use Waffle\Commons\Contracts\Attribute\Dto;
use Waffle\Commons\Contracts\Attribute\ListOf;
use Waffle\Commons\Utils\Dto\Shape\DtoShape;
use Waffle\Commons\Utils\Dto\Shape\DtoShapeReader;
use Waffle\Commons\Utils\Dto\Shape\Enum\TypeKind;
use Waffle\Commons\Utils\Dto\Shape\FieldType;
use Waffle\Commons\Utils\Dto\Shape\InputField;
use Waffle\Commons\Utils\Dto\Shape\OutputField;
use Waffle\Commons\Utils\Exception\UnsupportedDtoShapeException;
use WaffleTests\Commons\Utils\AbstractTestCase as TestCase;
use WaffleTests\Commons\Utils\Dto\Shape\Fixture\AbstractShape;
use WaffleTests\Commons\Utils\Dto\Shape\Fixture\Address;
use WaffleTests\Commons\Utils\Dto\Shape\Fixture\Customer;
use WaffleTests\Commons\Utils\Dto\Shape\Fixture\HiddenConstructor;
use WaffleTests\Commons\Utils\Dto\Shape\Fixture\Mood;
use WaffleTests\Commons\Utils\Dto\Shape\Fixture\Options;
use WaffleTests\Commons\Utils\Dto\Shape\Fixture\Order;
use WaffleTests\Commons\Utils\Dto\Shape\Fixture\OrderLine;
use WaffleTests\Commons\Utils\Dto\Shape\Fixture\Priority;
use WaffleTests\Commons\Utils\Dto\Shape\Fixture\Status;
use WaffleTests\Commons\Utils\Dto\Shape\Fixture\TreeNode;

use function array_keys;
use function array_map;

#[CoversClass(DtoShapeReader::class)]
#[CoversClass(DtoShape::class)]
#[CoversClass(FieldType::class)]
#[CoversClass(InputField::class)]
#[CoversClass(OutputField::class)]
#[CoversClass(UnsupportedDtoShapeException::class)]
final class DtoShapeReaderTest extends TestCase
{
    public function testInputsAreTheConstructorParametersInDeclarationOrder(): void
    {
        $shape = new DtoShapeReader()->read(Customer::class);

        static::assertSame(Customer::class, $shape->class);
        static::assertSame(['id', 'email', 'password', 'nickname'], $this->names($shape->inputs));
        static::assertSame(['string', 'string', 'string', '?string'], $this->types($shape->inputs));
        static::assertSame(
            [true, true, true, false],
            array_map(static fn(InputField $input): bool => $input->required, $shape->inputs),
        );

        $nickname = $shape->input('nickname');
        static::assertNotNull($nickname);
        static::assertTrue($nickname->hasDefault);
        static::assertNull($nickname->default);
    }

    public function testOutputsAreTheReadableProperties(): void
    {
        $shape = new DtoShapeReader()->read(Customer::class);

        // `alias` is write-only (a virtual property with only a `set` hook) and
        // `schema` is static: neither is an output. `password` goes in and
        // `passwordHash` comes out.
        static::assertSame(['email', 'passwordHash', 'display', 'id', 'nickname'], $this->names($shape->outputs));
        static::assertSame(
            [false, false, true, false, false],
            array_map(static fn(OutputField $output): bool => $output->computed, $shape->outputs),
        );
        static::assertNull($shape->output('password'));
        static::assertNull($shape->input('passwordHash'));
    }

    public function testNestedDtosListsAndAStringBackedEnum(): void
    {
        $shape = new DtoShapeReader()->read(Order::class);

        $status = $shape->input('status');
        static::assertNotNull($status);
        static::assertSame(TypeKind::Enum, $status->type->kind);
        static::assertSame(Status::class, $status->type->class);
        static::assertSame(TypeKind::String, $status->type->backing);

        $lines = $shape->input('lines');
        static::assertNotNull($lines);
        static::assertSame(TypeKind::List, $lines->type->kind);
        static::assertSame(TypeKind::Dto, $lines->type->item?->kind);
        static::assertSame(OrderLine::class, $lines->type->item->class);
        static::assertTrue($lines->required);

        $shipping = $shape->input('shipping');
        static::assertNotNull($shipping);
        static::assertSame('?' . Address::class, $shipping->type->describe());
        static::assertFalse($shipping->required);

        $tags = $shape->input('tags');
        static::assertNotNull($tags);
        static::assertSame('list<string>', $tags->type->describe());
        static::assertTrue($tags->hasDefault);
        static::assertSame([], $tags->default);

        static::assertSame([OrderLine::class, Address::class], $shape->dependencies());
    }

    public function testEveryKindOfDefaultValueAndAnIntBackedEnum(): void
    {
        $shape = new DtoShapeReader()->read(Options::class);

        static::assertSame(
            [Priority::Low, false, 0.5, 1, []],
            array_map(
                static fn(InputField $input): int|float|string|bool|BackedEnum|array|null => $input->default,
                $shape->inputs,
            ),
        );
        static::assertSame(
            [false, false, false, false, false],
            array_map(static fn(InputField $input): bool => $input->required, $shape->inputs),
        );

        $priority = $shape->input('priority');
        static::assertNotNull($priority);
        static::assertSame(TypeKind::Int, $priority->type->backing);

        $history = $shape->input('history');
        static::assertNotNull($history);
        static::assertSame('list<' . Status::class . '>', $history->type->describe());
        static::assertSame([], $shape->dependencies());
    }

    public function testSelfResolvesToTheDeclaringDto(): void
    {
        $shape = new DtoShapeReader()->read(TreeNode::class);

        static::assertSame(
            ['string', 'list<' . TreeNode::class . '>', '?' . TreeNode::class],
            $this->types($shape->inputs),
        );
        static::assertSame([TreeNode::class], $shape->dependencies());
    }

    public function testReadGraphReachesEveryNestedDtoOnce(): void
    {
        $reader = new DtoShapeReader();

        static::assertSame(
            [Order::class, OrderLine::class, Address::class],
            array_keys($reader->readGraph(Order::class)),
        );
        // A cycle (TreeNode refers to itself) and a repeated root are read once.
        static::assertSame(
            [TreeNode::class, Order::class, OrderLine::class, Address::class],
            array_keys($reader->readGraph(TreeNode::class, Order::class, TreeNode::class)),
        );
        static::assertSame([], $reader->readGraph());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function unreadableClasses(): iterable
    {
        yield 'a class without #[Dto]' => [stdClass::class, 'it is not marked #[Dto]'];
        yield 'an enum' => [Status::class, 'it is not a class'];
        yield 'a missing class' => ['WaffleTests\Commons\Utils\Dto\Shape\Fixture\Missing', 'it is not a class'];
        yield 'an abstract class' => [AbstractShape::class, 'it is not instantiable'];
        yield 'a private constructor' => [HiddenConstructor::class, 'it is not instantiable'];
    }

    #[DataProvider('unreadableClasses')]
    public function testRejectsAClassThatIsNotAConcreteDto(string $class, string $reason): void
    {
        try {
            /** @var class-string $class */
            new DtoShapeReader()->read($class);
            static::fail('The class was read.');
        } catch (UnsupportedDtoShapeException $exception) {
            static::assertSame($class, $exception->dtoClass);
            static::assertSame('', $exception->member);
            static::assertStringContainsString($reason, $exception->getMessage());
        }
    }

    /**
     * @return iterable<string, array{object, string, string}>
     */
    public static function unsupportedMembers(): iterable
    {
        yield 'an untyped parameter' => [
            new
                #[Dto]
                class(1) {
                    public function __construct(
                        public $value,
                    ) {}
                },
            'constructor parameter $value',
            'declares no type',
        ];
        yield 'a union' => [
            new
                #[Dto]
                class(1) {
                    public function __construct(
                        public int|string $value,
                    ) {}
                },
            'constructor parameter $value',
            'has the type string|int: a field has a single type',
        ];
        yield 'an intersection' => [
            new
                #[Dto]
                class(new ArrayIterator()) {
                    public function __construct(
                        public Countable&Traversable $items,
                    ) {}
                },
            'constructor parameter $items',
            'has the type Countable&Traversable: a field has a single type',
        ];
        yield 'an array without #[ListOf]' => [
            new
                #[Dto]
                class([]) {
                    /**
                     * @param list<int> $values
                     */
                    public function __construct(
                        public array $values,
                    ) {}
                },
            'constructor parameter $values',
            'is an array without #[ListOf]',
        ];
        yield '#[ListOf] on a scalar' => [
            new
                #[Dto]
                class(1) {
                    public function __construct(
                        #[ListOf('int')]
                        public int $value,
                    ) {}
                },
            'constructor parameter $value',
            'carries #[ListOf] on the type int',
        ];
        yield 'a list of lists' => [
            new
                #[Dto]
                class([]) {
                    /**
                     * @param list<int> $values
                     */
                    public function __construct(
                        #[ListOf('array')]
                        public array $values,
                    ) {}
                },
            'constructor parameter $values (#[ListOf] element)',
            'has the type array',
        ];
        yield 'a list of an unknown class' => [
            new
                #[Dto]
                class([]) {
                    /**
                     * @param list<int> $values
                     */
                    public function __construct(
                        #[ListOf('WaffleTests\Commons\Utils\Dto\Shape\Fixture\Missing')]
                        public array $values,
                    ) {}
                },
            'constructor parameter $values (#[ListOf] element)',
            'has the type WaffleTests\Commons\Utils\Dto\Shape\Fixture\Missing',
        ];
        yield 'a pure enum' => [
            new
                #[Dto]
                class(Mood::Happy) {
                    public function __construct(
                        public Mood $mood,
                    ) {}
                },
            'constructor parameter $mood',
            'has the pure enum ' . Mood::class,
        ];
        yield 'an object that is not a #[Dto]' => [
            new
                #[Dto]
                class(new DateTimeImmutable()) {
                    public function __construct(
                        public DateTimeImmutable $at,
                    ) {}
                },
            'constructor parameter $at',
            'has the type DateTimeImmutable: a field is a scalar, a backed enum, a #[Dto] class',
        ];
        yield 'a variadic parameter' => [
            new
                #[Dto]
                class('a', 'b') {
                    /** @var list<string> */
                    public private(set) array $tags;

                    public function __construct(string ...$tags)
                    {
                        $this->tags = array_values($tags);
                    }
                },
            'constructor parameter $tags',
            'is variadic',
        ];
        $counter = 0;
        yield 'a parameter passed by reference' => [
            new
                #[Dto]
                class($counter) {
                    public function __construct(int &$counter)
                    {
                        ++$counter;
                    }
                },
            'constructor parameter $counter',
            'is passed by reference',
        ];
        yield 'an object default value' => [
            new
                #[Dto]
                class() {
                    public function __construct(
                        public ?Address $address = new Address('1 rue Nationale', 'Lille'),
                    ) {}
                },
            'constructor parameter $address',
            'has a default value that is not null, a scalar, a backed enum case or []',
        ];
        yield 'a non-empty list default value' => [
            new
                #[Dto]
                class() {
                    /**
                     * @param list<string> $tags
                     */
                    public function __construct(
                        #[ListOf('string')]
                        public array $tags = ['new'],
                    ) {}
                },
            'constructor parameter $tags',
            'has a default value that is not null',
        ];
        yield 'an unsupported output property' => [
            new
                #[Dto]
                class() {
                    public ?Countable $meta = null;
                },
            'property $meta',
            'has the type Countable',
        ];
    }

    #[DataProvider('unsupportedMembers')]
    public function testRejectsAnUnsupportedMember(object $dto, string $member, string $reason): void
    {
        try {
            new DtoShapeReader()->read($dto::class);
            static::fail('The DTO was read.');
        } catch (UnsupportedDtoShapeException $exception) {
            static::assertSame($dto::class, $exception->dtoClass);
            static::assertSame($member, $exception->member);
            static::assertStringContainsString($member . ' ' . $reason, $exception->getMessage());
        }
    }

    /**
     * @param list<InputField>|list<OutputField> $fields
     *
     * @return list<string>
     */
    private function names(array $fields): array
    {
        return array_map(static fn(InputField|OutputField $field): string => $field->name, $fields);
    }

    /**
     * @param list<InputField>|list<OutputField> $fields
     *
     * @return list<string>
     */
    private function types(array $fields): array
    {
        return array_map(static fn(InputField|OutputField $field): string => $field->type->describe(), $fields);
    }
}

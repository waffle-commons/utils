<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Dto\Shape;

use BackedEnum;
use InvalidArgumentException;
use Waffle\Commons\Utils\Dto\Shape\Enum\TypeKind;

/**
 * The type of one DTO field: what the serializer checks a value against, and
 * what the OpenAPI schema describes.
 *
 * Built only through the named constructors, so every instance is a valid
 * combination: a scalar carries no class; a `#[Dto]` carries its class-string;
 * a backed enum carries its class-string and its backing kind; a list carries
 * its element type. A list element is never nullable and never a list.
 */
final readonly class FieldType
{
    /**
     * @param class-string|null $class
     */
    private function __construct(
        public private(set) TypeKind $kind,
        public private(set) bool $nullable,
        public private(set) ?string $class = null,
        public private(set) ?TypeKind $backing = null,
        public private(set) ?FieldType $item = null,
    ) {}

    /**
     * An `int`, `float`, `string` or `bool` field.
     *
     * @throws InvalidArgumentException When the kind is not a scalar kind.
     */
    public static function scalar(TypeKind $kind, bool $nullable = false): self
    {
        if (!$kind->isScalar()) {
            throw new InvalidArgumentException(sprintf('"%s" is not a scalar kind.', $kind->value));
        }

        return new self($kind, $nullable);
    }

    /**
     * A nested `#[Dto]` object.
     *
     * @param class-string $class
     */
    public static function dto(string $class, bool $nullable = false): self
    {
        return new self(TypeKind::Dto, $nullable, $class);
    }

    /**
     * A backed enum, carried in JSON as its backing value.
     *
     * @param class-string<BackedEnum> $class
     *
     * @throws InvalidArgumentException When the backing kind is neither `Int` nor `String`.
     */
    public static function enum(string $class, TypeKind $backing, bool $nullable = false): self
    {
        if ($backing !== TypeKind::Int && $backing !== TypeKind::String) {
            throw new InvalidArgumentException(sprintf(
                'An enum is backed by int or string, not "%s".',
                $backing->value,
            ));
        }

        return new self(TypeKind::Enum, $nullable, $class, $backing);
    }

    /**
     * A list of elements of one type.
     *
     * @throws InvalidArgumentException When the element type is nullable or a list itself.
     */
    public static function listOf(self $item, bool $nullable = false): self
    {
        if ($item->nullable || $item->kind === TypeKind::List) {
            throw new InvalidArgumentException('A list element is a non-nullable scalar, backed enum or #[Dto].');
        }

        return new self(TypeKind::List, $nullable, item: $item);
    }

    /**
     * The `#[Dto]` class this type refers to, directly or as a list element.
     *
     * @return class-string|null
     */
    public function referencedDto(): ?string
    {
        if ($this->kind === TypeKind::Dto) {
            return $this->class;
        }

        return $this->item?->referencedDto();
    }

    /**
     * The type in a PHP-like notation, for messages: `int`, `?string`,
     * `list<App\Dto\Line>`, `?App\Status`.
     */
    public function describe(): string
    {
        $name = match ($this->kind) {
            TypeKind::Int, TypeKind::Float, TypeKind::String, TypeKind::Bool => $this->kind->value,
            TypeKind::Dto, TypeKind::Enum => $this->class ?? $this->kind->value,
            TypeKind::List => sprintf('list<%s>', $this->item?->describe() ?? ''),
        };

        return $this->nullable ? '?' . $name : $name;
    }
}

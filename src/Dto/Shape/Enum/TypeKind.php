<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Dto\Shape\Enum;

/**
 * The kind of value a DTO field holds, as the DTO-shape reader sees it.
 *
 * The four scalar kinds map one-to-one onto JSON scalars. `Enum` is a backed
 * enum, whose JSON value is its backing value; `Dto` is a nested `#[Dto]`
 * object; `List` is a JSON array whose element type comes from `#[ListOf]`.
 */
enum TypeKind: string
{
    case Int = 'int';
    case Float = 'float';
    case String = 'string';
    case Bool = 'bool';
    case Enum = 'enum';
    case Dto = 'dto';
    case List = 'list';

    /**
     * Whether the kind is one of the four JSON scalars.
     */
    public function isScalar(): bool
    {
        return match ($this) {
            self::Int, self::Float, self::String, self::Bool => true,
            self::Enum, self::Dto, self::List => false,
        };
    }
}

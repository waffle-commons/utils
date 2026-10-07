<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Dto\Shape;

/**
 * One readable property of a DTO: a key the serialized form carries.
 *
 * A *computed* output is a virtual property with a `get` hook: it has no
 * backing value and no constructor parameter feeds it directly, so the OpenAPI
 * schema marks it read-only.
 */
final readonly class OutputField
{
    public function __construct(
        public private(set) string $name,
        public private(set) FieldType $type,
        public private(set) bool $computed = false,
    ) {}
}

<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Dto\Shape;

use BackedEnum;

/**
 * One constructor parameter of a DTO: a key the payload may carry.
 *
 * A payload is hydrated through the constructor with named arguments, so an
 * absent key falls back to the parameter's default. A nullable parameter
 * without a default receives `null` when its key is absent. Only a parameter
 * with neither is required.
 */
final readonly class InputField
{
    /** Whether the payload must carry the key: no default value and not nullable. */
    public private(set) bool $required;

    /**
     * @param int|float|string|bool|BackedEnum|array{}|null $default The default value, when `$hasDefault`.
     */
    public function __construct(
        public private(set) string $name,
        public private(set) FieldType $type,
        public private(set) bool $hasDefault = false,
        public private(set) int|float|string|bool|BackedEnum|array|null $default = null,
    ) {
        $this->required = !$hasDefault && !$type->nullable;
    }
}

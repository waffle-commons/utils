<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Exception;

use LogicException;

/**
 * A class cannot be read as a DTO shape.
 *
 * Either it is not a concrete `#[Dto]` class, or one of its members uses a
 * construct that the serializer and the OpenAPI generator cannot represent:
 * an untyped member, a union other than `?T`, an `array` without `#[ListOf]`,
 * a nested object that is neither a `#[Dto]` nor a backed enum, a default value
 * that is not a constant JSON value, and so on.
 *
 * It is raised at build time, while the shape is read. It reports a mistake in
 * the DTO, never a client error, so it extends `LogicException` and maps to no
 * HTTP status.
 */
final class UnsupportedDtoShapeException extends LogicException
{
    /**
     * @param string $dtoClass The class being read.
     * @param string $member   The member at fault (`constructor parameter $tags`,
     *                         `property $total`), or `''` for the class itself.
     * @param string $reason   Why it is unsupported, phrased to follow the member.
     */
    public function __construct(
        public private(set) string $dtoClass,
        public private(set) string $member,
        string $reason,
    ) {
        parent::__construct(sprintf(
            'Cannot read the DTO shape of "%s": %s %s.',
            $dtoClass,
            $member === '' ? 'it' : $member,
            $reason,
        ));
    }
}

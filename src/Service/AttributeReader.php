<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Service;

use ReflectionObject;

/**
 * Reads PHP attributes off arbitrary objects.
 *
 * Extracted from {@see \Waffle\Commons\Utils\Trait\ReflectionTrait} as part of
 * the Beta-1 Strategy refactor (audit: "Eradicate ReflectionTrait", Roadmap §1.2).
 * Falls back to constructing a zero-arg instance of the attribute class when the
 * target carries no matching attribute — preserves the original trait contract.
 */
final readonly class AttributeReader
{
    /**
     * @template T of object
     * @param class-string<T> $attribute
     * @return T
     */
    public function newAttributeInstance(object $target, string $attribute): object
    {
        $attributes = new ReflectionObject($target)->getAttributes($attribute);

        if (($attributes[0] ?? null) !== null) {
            /** @var T */
            return $attributes[0]->newInstance();
        }

        /** @var T */
        return new $attribute();
    }
}

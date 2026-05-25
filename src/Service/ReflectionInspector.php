<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Service;

use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;

/**
 * Object-shape inspection: finality, instance-of checks, property + method
 * enumeration.
 *
 * Introduced in Beta 1 as part of the Strategy refactor that replaced the
 * legacy `ReflectionTrait` (audit: "Eradicate ReflectionTrait", Roadmap §1.2).
 * Stateless; injectable via the container.
 */
final readonly class ReflectionInspector
{
    public function isFinal(object $object): bool
    {
        return new ReflectionObject($object)->isFinal();
    }

    /**
     * @param class-string[] $instances
     */
    public function isInstance(object $object, array $instances): bool
    {
        foreach ($instances as $instance) {
            if ($object instanceof $instance) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return ReflectionProperty[]
     */
    public function getProperties(object $object, ?int $filter = null): array
    {
        return new ReflectionObject($object)->getProperties(filter: $filter);
    }

    /**
     * @return ReflectionMethod[]
     */
    public function getMethods(object $object, ?int $filter = null): array
    {
        return new ReflectionObject($object)->getMethods(filter: $filter);
    }
}

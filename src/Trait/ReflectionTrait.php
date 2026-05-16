<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Trait;

use Generator;
use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;

trait ReflectionTrait
{
    /**
     * Gets the fully qualified class name from a file path using PHP Tokenizer.
     *
     * This method replaces the old Regex implementation. It is secure against ReDoS
     * and correctly handles PHP 8.x features (Enums, Readonly classes).
     *
     * @param string $path The absolute path to the PHP file.
     * @return string The FQCN or empty string if no class/interface/trait/enum is found.
     */
    public function className(string $path): string
    {
        if (!file_exists($path) || !is_readable($path)) {
            return '';
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return '';
        }

        // Secure Native Parsing using PHP Tokenizer
        $tokens = token_get_all($contents);

        $namespace = '';
        $class = '';

        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            // 1. Detect Namespace
            if (is_array($token) && $token[0] === T_NAMESPACE) {
                while (++$i < $count) {
                    if ($tokens[$i] === ';') {
                        $namespace = trim($namespace);
                        break;
                    }
                    if ($tokens[$i] === '{') {
                        // Handle bracketed namespace syntax
                        $namespace = trim($namespace);
                        break;
                    }

                    // Handle simple chars like separators if returned as strings
                    $namespace .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                }
                continue;
            }

            // 2. Detect Class / Interface / Trait / Enum
            if (is_array($token) && in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], strict: true)) {
                // We need to look forward to find the string name
                // This skips modifiers (readonly, final, abstract) and whitespace/comments

                // Skip if it is a resolution like MyClass::class (preceded by ::)
                // Note: token_get_all usually handles this context, but simple forward scan is safe for definitions
                // as long as we find T_STRING before T_DOUBLE_COLON or brackets.

                $j = $i;
                while (++$j < $count) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $class = $tokens[$j][1];
                        break 2; // Found the class name, stop everything
                    }

                    if ($tokens[$j] === '{' || is_array($tokens[$j]) && $tokens[$j][0] === T_CURLY_OPEN) {
                        // Anonymous class or class without name -> Stop searching for this token
                        break;
                    }
                }
            }
        }

        if ('' === $class) {
            return '';
        }

        return $namespace ? $namespace . '\\' . $class : $class;
    }

    /**
     * @param object $className
     * @param class-string $attribute
     * @return object
     */
    public function newAttributeInstance(object $className, string $attribute): object
    {
        $obj = new ReflectionObject(object: $className);
        $attributes = $obj->getAttributes(name: $attribute);

        if (($attributes[0] ?? null) !== null) {
            return $attributes[0]->newInstance();
        }

        // Fallback or specific logic if needed
        return new $attribute();
    }

    /**
     * @param array{
     *      classname: string,
     *      method: non-empty-string,
     *      arguments: array<non-empty-string, string>,
     *      path: string,
     *      name: non-falsy-string
     * }|null $route
     * @return Generator
     */
    public function controllerValues(?array $route = null): Generator
    {
        if (null === $route) {
            return;
        }

        foreach ($route as $key => $value) {
            yield $key => $value;
        }
    }

    /**
     * @param class-string[] $instances
     */
    private function isInstance(object $object, array $instances): bool
    {
        foreach ($instances as $instance) {
            if (!$object instanceof $instance) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function isFinal(object $object): bool
    {
        return new ReflectionObject($object)->isFinal();
    }

    /**
     * @param object $object
     * @param int|null $filter
     * @return ReflectionProperty[]
     */
    private function getProperties(object $object, ?int $filter = null): array
    {
        return new ReflectionObject($object)->getProperties(filter: $filter);
    }

    /**
     * @param object $object
     * @param int|null $filter
     * @return ReflectionMethod[]
     */
    private function getMethods(object $object, ?int $filter = null): array
    {
        return new ReflectionObject($object)->getMethods(filter: $filter);
    }
}

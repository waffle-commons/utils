<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Dto\Shape;

use BackedEnum;
use PropertyHookType;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use Waffle\Commons\Contracts\Attribute\Dto;
use Waffle\Commons\Contracts\Attribute\ListOf;
use Waffle\Commons\Utils\Dto\Shape\Enum\TypeKind;
use Waffle\Commons\Utils\Exception\UnsupportedDtoShapeException;

use function array_key_exists;
use function array_shift;
use function class_exists;
use function enum_exists;
use function is_scalar;
use function is_subclass_of;
use function sprintf;

/**
 * Reads the shape of a `#[Dto]` class at build time: constructor parameters in,
 * readable properties out (RFC-016, CARRY-04).
 *
 * The serializer's normalizer compiler and the OpenAPI schema builder both read
 * DTOs through this one reader, so the spec describes exactly what the
 * serializer accepts and emits.
 *
 *  - **Inputs** are the constructor parameters, in declaration order. A payload
 *    is hydrated through the constructor with named arguments, so the `set`
 *    hooks and the constructor body always run; nothing writes a property
 *    directly.
 *  - **Outputs** are the readable properties: public, non-static, and not a
 *    virtual property with only a `set` hook (that one is write-only). A virtual
 *    property with a `get` hook is a computed output. Asymmetric visibility
 *    (`public private(set)`, `public protected(set)`) and `readonly` change
 *    nothing for reading.
 *
 * The two sides are matched by name only, on purpose: a `password` parameter
 * hashed into a `passwordHash` property is accepted under one name and emitted
 * under the other.
 *
 * A field is an `int`, a `float`, a `string`, a `bool`, a backed enum, a nested
 * `#[Dto]` (`self` included), or an `array` whose element type `#[ListOf]`
 * declares; any of them may be nullable (`?T`, `T|null`). A default value is
 * `null`, a scalar, a backed enum case or `[]`. Anything else fails here with an
 * {@see UnsupportedDtoShapeException} that names the member.
 *
 * Build-time only: it reflects on every call and caches nothing, so it holds no
 * state across FrankenPHP worker requests.
 */
final readonly class DtoShapeReader
{
    /** @var array<string, TypeKind> */
    private const array SCALARS = [
        'int' => TypeKind::Int,
        'float' => TypeKind::Float,
        'string' => TypeKind::String,
        'bool' => TypeKind::Bool,
    ];

    /**
     * Reads one DTO.
     *
     * @param class-string $class
     *
     * @throws UnsupportedDtoShapeException
     */
    public function read(string $class): DtoShape
    {
        $reflection = $this->reflect($class);

        return new DtoShape($reflection->getName(), $this->readInputs($reflection), $this->readOutputs($reflection));
    }

    /**
     * Reads the given DTOs and every `#[Dto]` they reach, directly or through a
     * list, on either side. A cycle is followed once.
     *
     * @param class-string ...$roots
     *
     * @return array<class-string, DtoShape> Keyed by class name, in discovery order.
     *
     * @throws UnsupportedDtoShapeException
     */
    public function readGraph(string ...$roots): array
    {
        $queue = [];
        foreach ($roots as $root) {
            $queue[] = $this->reflect($root)->getName();
        }

        $shapes = [];
        while ($queue !== []) {
            $class = array_shift($queue);
            if (array_key_exists($class, $shapes)) {
                continue;
            }

            $shape = $this->read($class);
            $shapes[$class] = $shape;
            foreach ($shape->dependencies() as $dependency) {
                $queue[] = $dependency;
            }
        }

        return $shapes;
    }

    /**
     * @param class-string $class
     *
     * @return ReflectionClass<object>
     *
     * @throws UnsupportedDtoShapeException
     */
    private function reflect(string $class): ReflectionClass
    {
        if (!class_exists($class) || enum_exists($class)) {
            throw new UnsupportedDtoShapeException($class, '', 'is not a class');
        }

        $reflection = new ReflectionClass($class);
        if ($reflection->getAttributes(Dto::class) === []) {
            throw new UnsupportedDtoShapeException($class, '', 'is not marked #[Dto]');
        }

        if (!$reflection->isInstantiable()) {
            throw new UnsupportedDtoShapeException(
                $class,
                '',
                'is not instantiable: a DTO is a concrete class with a public constructor',
            );
        }

        return $reflection;
    }

    /**
     * @param ReflectionClass<object> $class
     *
     * @return list<InputField>
     *
     * @throws UnsupportedDtoShapeException
     */
    private function readInputs(ReflectionClass $class): array
    {
        $inputs = [];
        foreach ($class->getConstructor()?->getParameters() ?? [] as $parameter) {
            $inputs[] = $this->readInput($class->getName(), $parameter);
        }

        return $inputs;
    }

    /**
     * @param class-string $class
     *
     * @throws UnsupportedDtoShapeException
     */
    private function readInput(string $class, ReflectionParameter $parameter): InputField
    {
        $member = sprintf('constructor parameter $%s', $parameter->getName());

        if ($parameter->isVariadic()) {
            throw new UnsupportedDtoShapeException($class, $member, 'is variadic');
        }

        if ($parameter->isPassedByReference()) {
            throw new UnsupportedDtoShapeException($class, $member, 'is passed by reference');
        }

        $type = $this->resolve($class, $member, $parameter->getType(), $parameter->getAttributes(ListOf::class));

        if (!$parameter->isDefaultValueAvailable()) {
            return new InputField($parameter->getName(), $type);
        }

        return new InputField($parameter->getName(), $type, true, $this->defaultValue($class, $member, $parameter));
    }

    /**
     * @param ReflectionClass<object> $class
     *
     * @return list<OutputField>
     *
     * @throws UnsupportedDtoShapeException
     */
    private function readOutputs(ReflectionClass $class): array
    {
        $outputs = [];
        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            // A static property is not instance data; a virtual property without
            // a `get` hook is write-only.
            if ($property->isStatic() || $property->isVirtual() && !$property->hasHook(PropertyHookType::Get)) {
                continue;
            }

            $type = $this->resolve(
                $class->getName(),
                sprintf('property $%s', $property->getName()),
                $property->getType(),
                $property->getAttributes(ListOf::class),
            );
            $outputs[] = new OutputField($property->getName(), $type, $property->isVirtual());
        }

        return $outputs;
    }

    /**
     * Reflection already names `self` after the class that declares it.
     *
     * @param class-string                      $class  The DTO being read.
     * @param list<ReflectionAttribute<ListOf>> $listOf The member's `#[ListOf]`, if any.
     *
     * @throws UnsupportedDtoShapeException
     */
    private function resolve(string $class, string $member, ?ReflectionType $type, array $listOf): FieldType
    {
        if ($type === null) {
            throw new UnsupportedDtoShapeException($class, $member, 'declares no type');
        }

        // `?T` and `T|null` are both a named type; any other union, and every
        // intersection, is not.
        if (!$type instanceof ReflectionNamedType) {
            throw new UnsupportedDtoShapeException(
                $class,
                $member,
                sprintf('has the type %s: a field has a single type, optionally nullable', (string) $type),
            );
        }

        $name = $type->getName();
        $nullable = $type->allowsNull();
        $element = $listOf[0] ?? null;

        if ($name === 'array') {
            if ($element === null) {
                throw new UnsupportedDtoShapeException(
                    $class,
                    $member,
                    'is an array without #[ListOf]: declare the element type',
                );
            }

            $item = $this->typeOf($class, $member . ' (#[ListOf] element)', $element->newInstance()->type, false);

            return FieldType::listOf($item, $nullable);
        }

        if ($element !== null) {
            throw new UnsupportedDtoShapeException(
                $class,
                $member,
                sprintf('carries #[ListOf] on the type %s: it only applies to an array', $name),
            );
        }

        return $this->typeOf($class, $member, $name, $nullable);
    }

    /**
     * @throws UnsupportedDtoShapeException
     */
    private function typeOf(string $class, string $member, string $name, bool $nullable): FieldType
    {
        $scalar = self::SCALARS[$name] ?? null;
        if ($scalar !== null) {
            return FieldType::scalar($scalar, $nullable);
        }

        if (enum_exists($name)) {
            if (!is_subclass_of($name, BackedEnum::class)) {
                throw new UnsupportedDtoShapeException(
                    $class,
                    $member,
                    sprintf('has the pure enum %s: only a backed enum has a JSON value', $name),
                );
            }

            $backing = new ReflectionEnum($name)->getBackingType()?->getName() === 'int'
                ? TypeKind::Int
                : TypeKind::String;

            return FieldType::enum($name, $backing, $nullable);
        }

        if (class_exists($name) && new ReflectionClass($name)->getAttributes(Dto::class) !== []) {
            return FieldType::dto($name, $nullable);
        }

        throw new UnsupportedDtoShapeException(
            $class,
            $member,
            sprintf(
                'has the type %s: a field is a scalar, a backed enum, a #[Dto] class or an array with #[ListOf]',
                $name,
            ),
        );
    }

    /**
     * @param class-string $class
     *
     * @return int|float|string|bool|BackedEnum|array{}|null
     *
     * @throws UnsupportedDtoShapeException
     */
    private function defaultValue(
        string $class,
        string $member,
        ReflectionParameter $parameter,
    ): int|float|string|bool|BackedEnum|array|null {
        // Reflection returns the default untyped: it is kept in a one-element list
        // and narrowed in place, never bound on its own.
        $default = [$parameter->getDefaultValue()];

        if ($default[0] === null || is_scalar($default[0]) || $default[0] instanceof BackedEnum) {
            return $default[0];
        }

        if ($default[0] === []) {
            return [];
        }

        throw new UnsupportedDtoShapeException(
            $class,
            $member,
            'has a default value that is not null, a scalar, a backed enum case or []',
        );
    }
}

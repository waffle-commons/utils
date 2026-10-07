<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Dto\Shape;

use function in_array;

/**
 * The shape of one `#[Dto]` class: the keys it accepts and the keys it emits.
 *
 * Inputs are the constructor parameters and outputs the readable properties,
 * each in declaration order. The two sides are matched by name only: a
 * parameter without a readable property of the same name is write-only, and a
 * readable property without a parameter is read-only.
 */
final readonly class DtoShape
{
    /**
     * @param class-string      $class
     * @param list<InputField>  $inputs
     * @param list<OutputField> $outputs
     */
    public function __construct(
        public private(set) string $class,
        public private(set) array $inputs,
        public private(set) array $outputs,
    ) {}

    public function input(string $name): ?InputField
    {
        foreach ($this->inputs as $input) {
            if ($input->name === $name) {
                return $input;
            }
        }

        return null;
    }

    public function output(string $name): ?OutputField
    {
        foreach ($this->outputs as $output) {
            if ($output->name === $name) {
                return $output;
            }
        }

        return null;
    }

    /**
     * The `#[Dto]` classes this shape refers to, directly or as list elements,
     * on either side: each one once, in first-seen order.
     *
     * @return list<class-string>
     */
    public function dependencies(): array
    {
        $classes = [];
        foreach ([...$this->inputs, ...$this->outputs] as $field) {
            $class = $field->type->referencedDto();
            if ($class === null || in_array($class, $classes, true)) {
                continue;
            }

            $classes[] = $class;
        }

        return $classes;
    }
}

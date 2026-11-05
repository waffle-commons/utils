<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Dto\Shape\Fixture;

use Waffle\Commons\Contracts\Attribute\Dto;

#[Dto]
final readonly class HiddenConstructor
{
    private function __construct(
        public string $name,
    ) {}

    public static function named(string $name): self
    {
        return new self($name);
    }
}

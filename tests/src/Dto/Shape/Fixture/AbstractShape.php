<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Dto\Shape\Fixture;

use Waffle\Commons\Contracts\Attribute\Dto;

#[Dto]
abstract class AbstractShape
{
    public function __construct(
        public string $name,
    ) {}
}

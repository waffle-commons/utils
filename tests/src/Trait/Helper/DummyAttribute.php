<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Trait\Helper;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class DummyAttribute
{
    public function __construct(
        public string $value,
    ) {}
}

<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Dto\Shape\Fixture;

use Waffle\Commons\Contracts\Attribute\Dto;

#[Dto]
final readonly class Address
{
    public function __construct(
        public string $street,
        public string $city,
        public ?string $zip = null,
    ) {}
}

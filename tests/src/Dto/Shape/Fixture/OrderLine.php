<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Dto\Shape\Fixture;

use InvalidArgumentException;
use Waffle\Commons\Contracts\Attribute\Dto;

#[Dto]
final class OrderLine
{
    public function __construct(
        public private(set) string $sku,
        public private(set) int $quantity {
            set(int $value) {
                if ($value < 1) {
                    throw new InvalidArgumentException('A line holds at least one item.');
                }

                $this->quantity = $value;
            }
        },
        public private(set) float $unitPrice,
    ) {}
}

<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Dto\Shape\Fixture;

use Waffle\Commons\Contracts\Attribute\Dto;
use Waffle\Commons\Contracts\Attribute\ListOf;

/**
 * Nested DTOs, lists and a string-backed enum.
 */
#[Dto]
final readonly class Order
{
    /**
     * @param list<OrderLine> $lines
     * @param list<string>    $tags
     */
    public function __construct(
        public string $reference,
        public Status $status,
        #[ListOf(OrderLine::class)]
        public array $lines,
        public ?Address $shipping = null,
        #[ListOf('string')]
        public array $tags = [],
    ) {}
}

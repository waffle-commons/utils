<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Dto\Shape\Fixture;

use Waffle\Commons\Contracts\Attribute\Dto;
use Waffle\Commons\Contracts\Attribute\ListOf;

/**
 * Every kind of default value, and an int-backed enum.
 */
#[Dto]
final readonly class Options
{
    /**
     * @param list<Status> $history
     */
    public function __construct(
        public Priority $priority = Priority::Low,
        public bool $gift = false,
        public float $discount = 0.5,
        public int $quantity = 1,
        #[ListOf(Status::class)]
        public array $history = [],
    ) {}
}

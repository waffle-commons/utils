<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Dto\Shape\Fixture;

use Waffle\Commons\Contracts\Attribute\Dto;
use Waffle\Commons\Contracts\Attribute\ListOf;

/**
 * Refers to itself, through a list and through `self`.
 */
#[Dto]
final readonly class TreeNode
{
    /**
     * @param list<TreeNode> $children
     */
    public function __construct(
        public string $label,
        #[ListOf(self::class)]
        public array $children = [],
        public ?self $parent = null,
    ) {}
}

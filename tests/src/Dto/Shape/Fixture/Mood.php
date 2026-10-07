<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Dto\Shape\Fixture;

/**
 * A pure enum: it has no backing value, so it cannot be a DTO field.
 */
enum Mood
{
    case Happy;
}

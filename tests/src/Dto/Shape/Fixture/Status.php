<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Dto\Shape\Fixture;

enum Status: string
{
    case Draft = 'draft';
    case Paid = 'paid';
}

<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Exception;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Commons\Utils\Exception\UnsupportedDtoShapeException;
use WaffleTests\Commons\Utils\AbstractTestCase as TestCase;

#[CoversClass(UnsupportedDtoShapeException::class)]
final class UnsupportedDtoShapeExceptionTest extends TestCase
{
    public function testNamesTheClassTheMemberAndTheReason(): void
    {
        $exception = new UnsupportedDtoShapeException('App\Dto\Order', 'property $total', 'declares no type');

        static::assertInstanceOf(LogicException::class, $exception);
        static::assertSame('App\Dto\Order', $exception->dtoClass);
        static::assertSame('property $total', $exception->member);
        static::assertSame(
            'Cannot read the DTO shape of "App\Dto\Order": property $total declares no type.',
            $exception->getMessage(),
        );
    }

    public function testAClassLevelReasonReadsAsASentence(): void
    {
        $exception = new UnsupportedDtoShapeException('App\Dto\Order', '', 'is not marked #[Dto]');

        static::assertSame(
            'Cannot read the DTO shape of "App\Dto\Order": it is not marked #[Dto].',
            $exception->getMessage(),
        );
    }
}

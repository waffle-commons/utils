<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Clock;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Clock\ClockInterface;
use ReflectionClass;
use Waffle\Commons\Utils\Clock\SystemClock;
use WaffleTests\Commons\Utils\AbstractTestCase as TestCase;

#[CoversClass(SystemClock::class)]
final class SystemClockTest extends TestCase
{
    public function testIsAPsr20Clock(): void
    {
        static::assertInstanceOf(ClockInterface::class, new SystemClock());
    }

    public function testNowReadsTheSystemTime(): void
    {
        $clock = new SystemClock();

        $before = new DateTimeImmutable();
        $now = $clock->now();
        $after = new DateTimeImmutable();

        static::assertGreaterThanOrEqual($before, $now);
        static::assertLessThanOrEqual($after, $now);
    }

    public function testEveryCallReadsTheTimeAgain(): void
    {
        $clock = new SystemClock();

        $first = $clock->now();
        $second = $clock->now();

        static::assertNotSame($first, $second);
        static::assertGreaterThanOrEqual($first, $second);
    }

    public function testDefaultsToUtcWhateverTheAmbientTimeZone(): void
    {
        $now = new SystemClock()->now();

        static::assertSame('UTC', $now->getTimezone()->getName());
    }

    public function testUsesTheTimeZoneGivenAtConstruction(): void
    {
        $now = new SystemClock(new DateTimeZone('Europe/Paris'))->now();

        static::assertSame('Europe/Paris', $now->getTimezone()->getName());
    }

    public function testCarriesNoMutableState(): void
    {
        $reflection = new ReflectionClass(SystemClock::class);

        static::assertTrue($reflection->isFinal());
        static::assertTrue($reflection->isReadOnly());
        foreach ($reflection->getProperties() as $property) {
            static::assertFalse($property->isStatic());
        }
    }
}

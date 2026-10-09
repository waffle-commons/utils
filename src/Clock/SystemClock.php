<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Clock;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;

/**
 * PSR-20 clock that reads the system time ([CARRY-04]).
 *
 * Code that needs the current time takes a `ClockInterface` by injection and
 * receives this clock in production; tests swap in a frozen one. The time zone is
 * fixed at construction (UTC by default) instead of being read from the ambient
 * `date.timezone` setting, so the same code yields the same instant in every
 * environment.
 *
 * Worker-safe: every `now()` reads the time again, and the only field is the
 * readonly time zone.
 */
final readonly class SystemClock implements ClockInterface
{
    public function __construct(
        private DateTimeZone $timezone = new DateTimeZone('UTC'),
    ) {}

    #[\Override]
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->timezone);
    }
}

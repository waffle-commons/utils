<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Waffle\Commons\Utils\Assert;
use Waffle\Commons\Utils\Assert\FileAssertionsTrait;
use Waffle\Commons\Utils\Assert\NetworkAssertionsTrait;
use Waffle\Commons\Utils\Assert\NumericAssertionsTrait;
use Waffle\Commons\Utils\Assert\StringAssertionsTrait;
use WaffleTests\Commons\Utils\AbstractTestCase as TestCase;

use function gc_collect_cycles;
use function memory_get_usage;
use function sprintf;

/**
 * Guards the FrankenPHP resident-worker mandate: the assertion layer must carry
 * **no state** that survives a request loop. These tests fail the moment anyone
 * introduces a static cache or other mutable state into {@see Assert} or its
 * Strategy-A trait families.
 */
#[CoversClass(Assert::class)]
#[CoversTrait(StringAssertionsTrait::class)]
#[CoversTrait(NumericAssertionsTrait::class)]
#[CoversTrait(NetworkAssertionsTrait::class)]
#[CoversTrait(FileAssertionsTrait::class)]
final class WorkerSafetyTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string|trait-string}>
     */
    public static function statelessTargetsProvider(): iterable
    {
        yield 'Assert entry point' => [Assert::class];
        yield 'String trait' => [StringAssertionsTrait::class];
        yield 'Numeric trait' => [NumericAssertionsTrait::class];
        yield 'Network trait' => [NetworkAssertionsTrait::class];
        yield 'File trait' => [FileAssertionsTrait::class];
    }

    /**
     * @param class-string|trait-string $target
     */
    #[DataProvider('statelessTargetsProvider')]
    public function testNoStateIsDeclared(string $target): void
    {
        $properties = new ReflectionClass($target)->getProperties();

        foreach ($properties as $property) {
            static::assertFalse($property->isStatic(), sprintf(
                '%s::$%s is static — forbidden under FrankenPHP worker mode.',
                $target,
                $property->getName(),
            ));
        }

        // Strategy A keeps every family a pure function bag — zero properties at all.
        static::assertCount(0, $properties, sprintf('%s must declare no properties.', $target));
    }

    public function testAssertIsAFinalReadonlyNonInstantiableEntryPoint(): void
    {
        $reflection = new ReflectionClass(Assert::class);

        static::assertTrue($reflection->isFinal(), 'Assert must be final.');
        static::assertTrue($reflection->isReadOnly(), 'Assert must be readonly.');
        static::assertFalse($reflection->isInstantiable(), 'Assert must not be instantiable.');
    }

    public function testAssertionsAreDeterministicAndLeakNoMemoryAcrossWorkerLoops(): void
    {
        $expected = $this->sample();

        // Warm up so one-time allocations (opcache, interned strings) settle first.
        $this->sample();
        gc_collect_cycles();
        $baseline = memory_get_usage();

        for ($iteration = 0; $iteration < 1000; $iteration++) {
            if ($this->sample() === $expected) {
                continue;
            }

            static::fail(sprintf('Assert produced non-deterministic output on iteration %d.', $iteration));
        }

        gc_collect_cycles();

        // Same inputs → same outputs after 1000 simulated request loops...
        static::assertSame($expected, $this->sample());
        // ...and a leaking (stateful) implementation would have grown the heap here.
        static::assertLessThanOrEqual(
            $baseline,
            memory_get_usage(),
            'Assert retained memory across simulated worker loops.',
        );
    }

    /**
     * One representative call into every trait family; the returned tuple must be
     * byte-for-byte identical on every invocation.
     *
     * @return list<string|int|float>
     */
    private function sample(): array
    {
        return [
            Assert::email('  USER@Example.COM '),
            Assert::uuid('9B2E4C7A-1F3D-4A6B-8C2E-0F1A2B3C4D5E'),
            Assert::notEmpty('  trimmed  '),
            Assert::length('café', 1, 10),
            Assert::range(42, 18, 130),
            Assert::positive(7),
            Assert::ip('2001:DB8::1'),
            Assert::port(8080),
            Assert::cidr('192.168.0.0/24'),
        ];
    }
}

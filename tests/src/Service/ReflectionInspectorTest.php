<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use stdClass;
use Waffle\Commons\Utils\Service\ReflectionInspector;
use WaffleTests\Commons\Utils\AbstractTestCase as TestCase;
use WaffleTests\Commons\Utils\Trait\Helper\DummyClassWithAttribute;
use WaffleTests\Commons\Utils\Trait\Helper\FinalReadOnlyClass;
use WaffleTests\Commons\Utils\Trait\Helper\NonFinalTestController;

#[CoversClass(ReflectionInspector::class)]
final class ReflectionInspectorTest extends TestCase
{
    public function testIsFinalDetectsFinality(): void
    {
        $inspector = new ReflectionInspector();
        static::assertTrue($inspector->isFinal(new FinalReadOnlyClass()));
        static::assertFalse($inspector->isFinal(new NonFinalTestController()));
    }

    public function testIsInstanceMatchesAcrossArrayOfFqcns(): void
    {
        $inspector = new ReflectionInspector();
        $object = new DummyClassWithAttribute();

        static::assertTrue($inspector->isInstance($object, [DummyClassWithAttribute::class]));
        static::assertTrue($inspector->isInstance($object, [stdClass::class, DummyClassWithAttribute::class]));
        static::assertFalse($inspector->isInstance($object, [stdClass::class]));
        static::assertFalse($inspector->isInstance($object, []));
    }

    public function testGetPropertiesRespectsFilter(): void
    {
        $object = new class {
            public string $a = '';
            protected string $b = '';
            private string $c = '';
        };

        $inspector = new ReflectionInspector();
        static::assertCount(3, $inspector->getProperties($object));

        $public = $inspector->getProperties($object, ReflectionProperty::IS_PUBLIC);
        static::assertCount(1, $public);
        $first = $public[0] ?? null;
        static::assertNotNull($first);
        static::assertSame('a', $first->getName());
    }

    public function testGetMethodsReturnsArrayOfReflectionMethods(): void
    {
        $inspector = new ReflectionInspector();
        $methods = $inspector->getMethods(new DummyClassWithAttribute());

        static::assertGreaterThanOrEqual(1, count($methods));
        static::assertContainsOnlyInstancesOf(\ReflectionMethod::class, $methods);
    }
}

<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Waffle\Commons\Utils\Service\AttributeReader;
use WaffleTests\Commons\Utils\AbstractTestCase as TestCase;
use WaffleTests\Commons\Utils\Trait\Helper\DummyAttribute;
use WaffleTests\Commons\Utils\Trait\Helper\DummyClassWithAttribute;

#[CoversClass(AttributeReader::class)]
final class AttributeReaderTest extends TestCase
{
    public function testReadsDeclaredAttributeInstance(): void
    {
        $reader = new AttributeReader();
        $attribute = $reader->newAttributeInstance(new DummyClassWithAttribute(), DummyAttribute::class);

        static::assertInstanceOf(DummyAttribute::class, $attribute);
        static::assertSame('test-value', $attribute->value);
    }

    public function testFallsBackToZeroArgConstructorWhenAttributeMissing(): void
    {
        $reader = new AttributeReader();
        $target = new class {};

        $result = $reader->newAttributeInstance($target, stdClass::class);

        static::assertInstanceOf(stdClass::class, $result);
    }
}

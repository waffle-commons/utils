<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Utils\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Commons\Contracts\Constant\Constant;
use Waffle\Commons\Utils\Service\ClassParser;
use WaffleTests\Commons\Utils\AbstractTestCase as TestCase;

#[CoversClass(ClassParser::class)]
final class ClassParserTest extends TestCase
{
    /** @var list<string> */
    private array $createdFiles = [];
    private ClassParser $parser;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new ClassParser();
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        foreach ($this->createdFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }
            unlink($file);
        }
    }

    private function tmp(string $content): string
    {
        $filename = sys_get_temp_dir() . '/waffle_classparser_' . uniqid('', more_entropy: true) . '.php';
        file_put_contents($filename, "<?php\n" . $content);
        $this->createdFiles[] = $filename;
        return $filename;
    }

    public function testReturnsEmptyStringForNonExistentFile(): void
    {
        static::assertSame(Constant::EMPTY_STRING, $this->parser->className('/no/such/path.php'));
    }

    public function testReturnsEmptyStringForEmptyFile(): void
    {
        static::assertSame(Constant::EMPTY_STRING, $this->parser->className($this->tmp('')));
    }

    public function testDetectsSimpleClass(): void
    {
        static::assertSame('SimpleClass', $this->parser->className($this->tmp('class SimpleClass {}')));
    }

    public function testDetectsNamespacedClass(): void
    {
        $file = $this->tmp('namespace App\\Test; class MyClass {}');
        static::assertSame('App\\Test\\MyClass', $this->parser->className($file));
    }

    public function testDetectsBracketedNamespace(): void
    {
        $file = $this->tmp('namespace App\\Bracket { class InBracket {} }');
        static::assertSame('App\\Bracket\\InBracket', $this->parser->className($file));
    }

    public function testDetectsInterface(): void
    {
        $file = $this->tmp('namespace App; interface MyInterface {}');
        static::assertSame('App\\MyInterface', $this->parser->className($file));
    }

    public function testDetectsTrait(): void
    {
        $file = $this->tmp('namespace App\\Traits; trait MyTrait {}');
        static::assertSame('App\\Traits\\MyTrait', $this->parser->className($file));
    }

    public function testDetectsEnum(): void
    {
        $file = $this->tmp('namespace App\\Enums; enum Status {}');
        static::assertSame('App\\Enums\\Status', $this->parser->className($file));
    }

    public function testIgnoresResolutionOperatorBeforeDefinition(): void
    {
        $content = <<<PHP
            namespace App;
            use Other\\Service;
            \$name = Service::class;
            final class RealDefinition {}
            PHP;
        static::assertSame('App\\RealDefinition', $this->parser->className($this->tmp($content)));
    }

    public function testHandlesComplexSpacingAndComments(): void
    {
        $content = <<<PHP
            namespace   App\\Complex  ;
            /**
             * Docblock
             */
            abstract   class   SpacedClass  {}
            PHP;
        static::assertSame('App\\Complex\\SpacedClass', $this->parser->className($this->tmp($content)));
    }

    public function testReturnsEmptyStringWhenNoClassDefined(): void
    {
        static::assertSame(Constant::EMPTY_STRING, $this->parser->className($this->tmp('namespace App; $x = 1;')));
    }
}

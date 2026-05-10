<?php

declare(strict_types=1);

namespace Tests\Unit\Interfaces;

use App\Interfaces\IDocumentReader;
use Tests\Support\TestCase;

class DocumentReaderInterfaceTest extends TestCase
{
    public function testIDocumentReaderInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(IDocumentReader::class));
    }

    public function testIDocumentReaderHasReadMethod(): void
    {
        $reflection = new \ReflectionClass(IDocumentReader::class);
        $this->assertTrue($reflection->hasMethod('read'));

        $method = $reflection->getMethod('read');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isAbstract());

        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('directory', $params[0]->getName());
        $this->assertEquals('string', (string) $params[0]->getType());
    }

    public function testIDocumentReaderReadReturnsDocumentArray(): void
    {
        $reflection = new \ReflectionClass(IDocumentReader::class);
        $method = $reflection->getMethod('read');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', (string) $returnType);
    }
}

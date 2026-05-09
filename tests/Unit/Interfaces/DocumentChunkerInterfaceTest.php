<?php

declare(strict_types=1);

namespace Tests\Unit\Interfaces;

use App\Interfaces\IDocumentChunker;
use Tests\Support\TestCase;

class DocumentChunkerInterfaceTest extends TestCase
{
    public function testIdocumentChunkerInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(IDocumentChunker::class));
    }

    public function testIdocumentChunkerHasChunkMethod(): void
    {
        $reflection = new \ReflectionClass(IDocumentChunker::class);
        $this->assertTrue($reflection->hasMethod("chunk"));

        $method = $reflection->getMethod("chunk");
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isAbstract());

        $params = $method->getParameters();
        $this->assertCount(4, $params);
        $this->assertEquals("document", $params[0]->getName());
        $this->assertEquals("maxLength", $params[1]->getName());
        $this->assertEquals("separator", $params[2]->getName());
        $this->assertEquals("wordOverlap", $params[3]->getName());
    }

    public function testIdocumentChunkerChunkReturnsDocumentArray(): void
    {
        $reflection = new \ReflectionClass(IDocumentChunker::class);
        $method = $reflection->getMethod("chunk");

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals("array", (string) $returnType);
    }
}

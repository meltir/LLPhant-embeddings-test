<?php

declare(strict_types=1);

namespace Tests\Unit\Interfaces;

use App\Interfaces\IDocumentPreprocessor;
use LLPhant\Embeddings\Document;
use Tests\Support\TestCase;

class DocumentPreprocessorInterfaceTest extends TestCase
{
    public function testIdocumentPreprocessorInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(IDocumentPreprocessor::class));
    }

    public function testIdocumentPreprocessorHasPreprocessMethod(): void
    {
        $reflection = new \ReflectionClass(IDocumentPreprocessor::class);
        $this->assertTrue($reflection->hasMethod("preprocess"));

        $method = $reflection->getMethod("preprocess");
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isAbstract());

        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertEquals("document", $params[0]->getName());
        $this->assertEquals("title", $params[1]->getName());
    }

    public function testIdocumentPreprocessorPreprocessReturnsDocument(): void
    {
        $reflection = new \ReflectionClass(IDocumentPreprocessor::class);
        $method = $reflection->getMethod("preprocess");

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals(Document::class, (string) $returnType);
    }
}

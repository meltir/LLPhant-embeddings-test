<?php

declare(strict_types=1);

namespace Tests\Unit\Interfaces;

use App\Interfaces\IVectorSearch;
use Tests\Support\TestCase;

class VectorSearchInterfaceTest extends TestCase
{
    public function testIvectorSearchInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(IVectorSearch::class));
    }

    public function testIvectorSearchHasSearchMethod(): void
    {
        $reflection = new \ReflectionClass(IVectorSearch::class);
        $this->assertTrue($reflection->hasMethod("search"));

        $method = $reflection->getMethod("search");
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isAbstract());

        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertEquals("embedding", $params[0]->getName());
        $this->assertEquals("k", $params[1]->getName());
    }

    public function testIvectorSearchSearchReturnsDocumentArray(): void
    {
        $reflection = new \ReflectionClass(IVectorSearch::class);
        $method = $reflection->getMethod("search");

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals("array", (string) $returnType);
    }

    public function testIvectorSearchSearchDefaultKIsFour(): void
    {
        $reflection = new \ReflectionClass(IVectorSearch::class);
        $method = $reflection->getMethod("search");

        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertTrue($params[1]->isOptional());
        $this->assertEquals(4, $params[1]->getDefaultValue());
    }
}

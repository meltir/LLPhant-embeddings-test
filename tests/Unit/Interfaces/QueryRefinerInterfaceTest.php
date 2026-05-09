<?php

declare(strict_types=1);

namespace Tests\Unit\Interfaces;

use App\Interfaces\IQueryRefiner;
use Tests\Support\TestCase;

class QueryRefinerInterfaceTest extends TestCase
{
    public function testIqueryRefinerInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(IQueryRefiner::class));
    }

    public function testIqueryRefinerHasRefineMethod(): void
    {
        $reflection = new \ReflectionClass(IQueryRefiner::class);
        $this->assertTrue($reflection->hasMethod("refine"));

        $method = $reflection->getMethod("refine");
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isAbstract());

        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals("question", $params[0]->getName());
    }

    public function testIqueryRefinerRefineReturnsString(): void
    {
        $reflection = new \ReflectionClass(IQueryRefiner::class);
        $method = $reflection->getMethod("refine");

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals("string", (string) $returnType);
    }
}

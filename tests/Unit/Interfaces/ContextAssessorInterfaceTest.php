<?php

declare(strict_types=1);

namespace Tests\Unit\Interfaces;

use App\Interfaces\IContextAssessor;
use Tests\Support\TestCase;

class ContextAssessorInterfaceTest extends TestCase
{
    public function testIcontextAssessorInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(IContextAssessor::class));
    }

    public function testIcontextAssessorHasAssessMethod(): void
    {
        $reflection = new \ReflectionClass(IContextAssessor::class);
        $this->assertTrue($reflection->hasMethod("assess"));

        $method = $reflection->getMethod("assess");
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isAbstract());

        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertEquals("question", $params[0]->getName());
        $this->assertEquals("context", $params[1]->getName());
    }

    public function testIcontextAssessorAssessReturnsString(): void
    {
        $reflection = new \ReflectionClass(IContextAssessor::class);
        $method = $reflection->getMethod("assess");

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals("string", (string) $returnType);
    }
}

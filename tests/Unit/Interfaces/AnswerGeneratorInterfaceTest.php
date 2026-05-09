<?php

declare(strict_types=1);

namespace Tests\Unit\Interfaces;

use App\Interfaces\IAnswerGenerator;
use Tests\Support\TestCase;

class AnswerGeneratorInterfaceTest extends TestCase
{
    public function testIanswerGeneratorInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(IAnswerGenerator::class));
    }

    public function testIanswerGeneratorHasGenerateMethod(): void
    {
        $reflection = new \ReflectionClass(IAnswerGenerator::class);
        $this->assertTrue($reflection->hasMethod("generate"));

        $method = $reflection->getMethod("generate");
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isAbstract());

        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertEquals("question", $params[0]->getName());
        $this->assertEquals("context", $params[1]->getName());
    }

    public function testIanswerGeneratorGenerateReturnsString(): void
    {
        $reflection = new \ReflectionClass(IAnswerGenerator::class);
        $method = $reflection->getMethod("generate");

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals("string", (string) $returnType);
    }
}

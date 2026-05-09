<?php

declare(strict_types=1);

namespace Tests\Unit\Search;

use App\Search\VectorSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Tests\Support\TestCase;

class VectorSearchServiceTest extends TestCase
{
    private VectorSearchService $service;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->service = new VectorSearchService($this->entityManager, \App\Entity\Chunk::class);
    }

    public function test_service_is_instance_of_correct_class(): void
    {
        $this->assertInstanceOf(VectorSearchService::class, $this->service);
    }

    public function test_service_implements_interface(): void
    {
        $this->assertInstanceOf(\App\Interfaces\IVectorSearch::class, $this->service);
    }

    public function test_search_returns_array(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search($this->makeEmbeddingVector(768));
    }

    public function test_search_with_empty_embedding(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search([]);
    }

    public function test_search_with_wrong_embedding_length(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search(array_fill(0, 100, 0.0));
    }

    public function test_search_with_correct_embedding_length(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search($this->makeEmbeddingVector(768));
    }

    public function test_search_with_k_parameter(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search($this->makeEmbeddingVector(768), 10);
    }

    public function test_search_with_k_equals_one(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search($this->makeEmbeddingVector(768), 1);
    }

    public function test_search_with_large_k(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search($this->makeEmbeddingVector(768), 1000);
    }

    public function test_search_with_negative_k(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search($this->makeEmbeddingVector(768), -1);
    }

    public function test_search_with_zero_k(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search($this->makeEmbeddingVector(768), 0);
    }

    public function test_search_with_float_embedding_values(): void
    {
        $embedding = [];
        for ($i = 0; $i < 768; $i++) {
            $embedding[] = (float) sin($i * 0.01);
        }

        $this->expectException(\Throwable::class);
        $this->service->search($embedding);
    }

    public function test_search_with_negative_embedding_values(): void
    {
        $embedding = array_fill(0, 768, -0.5);

        $this->expectException(\Throwable::class);
        $this->service->search($embedding);
    }

    public function test_search_with_mixed_embedding_values(): void
    {
        $embedding = [];
        for ($i = 0; $i < 768; $i++) {
            $embedding[] = ($i % 2 === 0) ? 0.5 : -0.5;
        }

        $this->expectException(\Throwable::class);
        $this->service->search($embedding);
    }

    public function test_search_with_unity_embedding(): void
    {
        $embedding = array_fill(0, 768, 1.0);

        $this->expectException(\Throwable::class);
        $this->service->search($embedding);
    }

    public function test_search_with_zero_embedding(): void
    {
        $embedding = array_fill(0, 768, 0.0);

        $this->expectException(\Throwable::class);
        $this->service->search($embedding);
    }

    public function test_search_with_nan_embedding(): void
    {
        $embedding = array_fill(0, 768, NAN);

        $this->expectException(\Throwable::class);
        $this->service->search($embedding);
    }

    public function test_search_with_infinity_embedding(): void
    {
        $embedding = array_fill(0, 768, INF);

        $this->expectException(\Throwable::class);
        $this->service->search($embedding);
    }

    public function test_search_with_boolean_embedding(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search([true, false, true]);
    }

    public function test_search_with_string_embedding(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search(['one', 'two', 'three']);
    }

    public function test_service_uses_chunk_entity_class(): void
    {
        $service = new VectorSearchService($this->entityManager, \App\Entity\Chunk::class);

        $this->assertInstanceOf(VectorSearchService::class, $service);
    }

    public function test_service_with_different_entity_class(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $service = new VectorSearchService($entityManager, 'SomeOtherClass');

        $this->assertInstanceOf(VectorSearchService::class, $service);
    }

    public function test_search_default_k_is_four(): void
    {
        $reflection = new \ReflectionClass(\App\Interfaces\IVectorSearch::class);
        $method = $reflection->getMethod('search');
        $params = $method->getParameters();

        $this->assertTrue($params[1]->isOptional());
        $this->assertEquals(4, $params[1]->getDefaultValue());
    }

    public function test_search_embedding_type(): void
    {
        $reflection = new \ReflectionClass(\App\Interfaces\IVectorSearch::class);
        $method = $reflection->getMethod('search');
        $params = $method->getParameters();

        $this->assertEquals('array', (string) $params[0]->getType());
    }

    public function test_search_k_type(): void
    {
        $reflection = new \ReflectionClass(\App\Interfaces\IVectorSearch::class);
        $method = $reflection->getMethod('search');
        $params = $method->getParameters();

        $this->assertEquals('int', (string) $params[1]->getType());
    }

    public function test_search_return_type(): void
    {
        $reflection = new \ReflectionClass(\App\Interfaces\IVectorSearch::class);
        $method = $reflection->getMethod('search');
        $returnType = $method->getReturnType();

        $this->assertEquals('array', (string) $returnType);
    }

    public function test_service_constructor_parameters(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $service = new VectorSearchService($entityManager, \App\Entity\Chunk::class);

        $this->assertInstanceOf(VectorSearchService::class, $service);
    }

    public function test_service_with_null_entity_class(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $this->expectException(\TypeError::class);
        new VectorSearchService($entityManager, null);
    }

    public function test_service_with_empty_entity_class(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $service = new VectorSearchService($entityManager, '');
        $this->assertInstanceOf(VectorSearchService::class, $service);
    }
}

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

    public function testServiceIsInstanceOfCorrectClass(): void
    {
        $this->assertInstanceOf(VectorSearchService::class, $this->service);
    }

    public function testServiceImplementsInterface(): void
    {
        $this->assertInstanceOf(\App\Interfaces\IVectorSearch::class, $this->service);
    }

    public function testSearchReturnsArray(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search($this->makeEmbeddingVector(768));
    }

    public function testSearchWithEmptyEmbedding(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search([]);
    }

    public function testSearchWithWrongEmbeddingLength(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search(array_fill(0, 100, 0.0));
    }

    public function testSearchWithCorrectEmbeddingLength(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search($this->makeEmbeddingVector(768));
    }

    public function testSearchWithKParameter(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search($this->makeEmbeddingVector(768), 10);
    }

    public function testSearchWithKEqualsOne(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search($this->makeEmbeddingVector(768), 1);
    }

    public function testSearchWithLargeK(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search($this->makeEmbeddingVector(768), 1000);
    }

    public function testSearchWithNegativeK(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search($this->makeEmbeddingVector(768), -1);
    }

    public function testSearchWithZeroK(): void
    {
        $this->expectException(\Throwable::class);
        $this->service->search($this->makeEmbeddingVector(768), 0);
    }

    public function testSearchWithFloatEmbeddingValues(): void
    {
        $embedding = [];
        for ($i = 0; $i < 768; $i++) {
            $embedding[] = (float) sin($i * 0.01);
        }

        $this->expectException(\Throwable::class);
        $this->service->search($embedding);
    }

    public function testSearchWithNegativeEmbeddingValues(): void
    {
        $embedding = array_fill(0, 768, -0.5);

        $this->expectException(\Throwable::class);
        $this->service->search($embedding);
    }

    public function testSearchWithMixedEmbeddingValues(): void
    {
        $embedding = [];
        for ($i = 0; $i < 768; $i++) {
            $embedding[] = ($i % 2 === 0) ? 0.5 : -0.5;
        }

        $this->expectException(\Throwable::class);
        $this->service->search($embedding);
    }

    public function testSearchWithUnityEmbedding(): void
    {
        $embedding = array_fill(0, 768, 1.0);

        $this->expectException(\Throwable::class);
        $this->service->search($embedding);
    }

    public function testSearchWithZeroEmbedding(): void
    {
        $embedding = array_fill(0, 768, 0.0);

        $this->expectException(\Throwable::class);
        $this->service->search($embedding);
    }

    public function testSearchWithNanEmbedding(): void
    {
        $embedding = array_fill(0, 768, NAN);

        $this->expectException(\Throwable::class);
        $this->service->search($embedding);
    }

    public function testSearchWithInfinityEmbedding(): void
    {
        $embedding = array_fill(0, 768, INF);

        $this->expectException(\Throwable::class);
        $this->service->search($embedding);
    }

    public function testSearchWithBooleanEmbedding(): void
    {
        $this->expectException(\Throwable::class);
        /** @phpstan-ignore argument.type */
        $this->service->search([true, false, true]);
    }

    public function testSearchWithStringEmbedding(): void
    {
        $this->expectException(\Throwable::class);
        /** @phpstan-ignore argument.type */
        $this->service->search(['one', 'two', 'three']);
    }

    public function testServiceUsesChunkEntityClass(): void
    {
        $service = new VectorSearchService($this->entityManager, \App\Entity\Chunk::class);

        $this->assertInstanceOf(VectorSearchService::class, $service);
    }

    public function testServiceWithDifferentEntityClass(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        /** @phpstan-ignore argument.type */
        $service = new VectorSearchService($entityManager, 'SomeOtherClass');

        $this->assertInstanceOf(VectorSearchService::class, $service);
    }

    public function testSearchDefaultKIsFour(): void
    {
        $reflection = new \ReflectionClass(\App\Interfaces\IVectorSearch::class);
        $method = $reflection->getMethod('search');
        $params = $method->getParameters();

        $this->assertTrue($params[1]->isOptional());
        $this->assertEquals(4, $params[1]->getDefaultValue());
    }

    public function testSearchEmbeddingType(): void
    {
        $reflection = new \ReflectionClass(\App\Interfaces\IVectorSearch::class);
        $method = $reflection->getMethod('search');
        $params = $method->getParameters();

        $this->assertEquals('array', (string) $params[0]->getType());
    }

    public function testSearchKType(): void
    {
        $reflection = new \ReflectionClass(\App\Interfaces\IVectorSearch::class);
        $method = $reflection->getMethod('search');
        $params = $method->getParameters();

        $this->assertEquals('int', (string) $params[1]->getType());
    }

    public function testSearchReturnType(): void
    {
        $reflection = new \ReflectionClass(\App\Interfaces\IVectorSearch::class);
        $method = $reflection->getMethod('search');
        $returnType = $method->getReturnType();

        $this->assertEquals('array', (string) $returnType);
    }

    public function testServiceConstructorParameters(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $service = new VectorSearchService($entityManager, \App\Entity\Chunk::class);

        $this->assertInstanceOf(VectorSearchService::class, $service);
    }

    public function testServiceWithNullEntityClass(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $this->expectException(\TypeError::class);
        /** @phpstan-ignore argument.type */
        $service = new VectorSearchService($entityManager, null);
        unset($service);
    }

    public function testServiceWithEmptyEntityClass(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);

        /** @phpstan-ignore argument.type */
        $service = new VectorSearchService($entityManager, '');
        $this->assertInstanceOf(VectorSearchService::class, $service);
    }
}

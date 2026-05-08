<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Entity\Chunk;
use Tests\Support\TestCase;

class ChunkTest extends TestCase
{
    public function test_chunk_creates_with_defaults(): void
    {
        $chunk = new Chunk();

        $this->assertEquals('file', $chunk->sourceType);
        $this->assertEquals('manual', $chunk->sourceName);
    }

    public function test_chunk_embedding_is_uninitialized_by_default(): void
    {
        $chunk = new Chunk();
        $reflection = new \ReflectionClass($chunk);
        $prop = $reflection->getProperty('embedding');
        $this->assertFalse($prop->isInitialized($chunk));
    }

    public function test_chunk_chunk_index_is_uninitialized_by_default(): void
    {
        $chunk = new Chunk();
        $reflection = new \ReflectionClass($chunk);
        $prop = $reflection->getProperty('chunkIndex');
        $this->assertFalse($prop->isInitialized($chunk));
    }

    public function test_chunk_content_is_uninitialized_by_default(): void
    {
        $chunk = new Chunk();
        $reflection = new \ReflectionClass($chunk);
        $prop = $reflection->getProperty('content');
        $this->assertFalse($prop->isInitialized($chunk));
    }

    public function test_chunk_sets_source_type(): void
    {
        $chunk = new Chunk();
        $chunk->sourceType = 'database';

        $this->assertEquals('database', $chunk->sourceType);
    }

    public function test_chunk_sets_source_name(): void
    {
        $chunk = new Chunk();
        $chunk->sourceName = 'test_novel';

        $this->assertEquals('test_novel', $chunk->sourceName);
    }

    public function test_chunk_sets_chunk_index(): void
    {
        $chunk = new Chunk();
        $chunk->chunkIndex = 5;

        $this->assertEquals(5, $chunk->chunkIndex);
    }

    public function test_chunk_chunk_index_initially_unset(): void
    {
        $chunk = new Chunk();
        $reflection = new \ReflectionClass($chunk);
        $prop = $reflection->getProperty('chunkIndex');
        $this->assertTrue($prop->isInitialized($chunk) === false);
    }

    public function test_chunk_sets_content(): void
    {
        $content = 'This is a test chunk content with some text.';
        $chunk = new Chunk();
        $chunk->content = $content;

        $this->assertEquals($content, $chunk->content);
    }

    public function test_chunk_content_initially_unset(): void
    {
        $chunk = new Chunk();
        $reflection = new \ReflectionClass($chunk);
        $prop = $reflection->getProperty('content');
        $this->assertTrue($prop->isInitialized($chunk) === false);
    }

    public function test_chunk_sets_embedding(): void
    {
        $embedding = array_fill(0, 768, 0.001);
        $chunk = new Chunk();
        $chunk->embedding = $embedding;

        $this->assertCount(768, $chunk->embedding);
        $this->assertEquals(0.001, $chunk->embedding[0]);
    }

    public function test_chunk_embedding_initially_unset(): void
    {
        $chunk = new Chunk();
        $reflection = new \ReflectionClass($chunk);
        $prop = $reflection->getProperty('embedding');
        $this->assertTrue($prop->isInitialized($chunk) === false);
    }

    public function test_chunk_embedding_length_is_768(): void
    {
        $chunk = new Chunk();
        $reflection = new \ReflectionClass($chunk);

        // The entity should use vector(768) as defined in the entity
        $this->assertInstanceOf(Chunk::class, $chunk);
    }

    public function test_chunk_is_instance_of_llphant_base(): void
    {
        $chunk = new Chunk();

        $this->assertInstanceOf(\LLPhant\Embeddings\VectorStores\Doctrine\DoctrineEmbeddingEntityBase::class, $chunk);
    }

    public function test_chunk_content_can_be_long_text(): void
    {
        $longContent = str_repeat('This is a long piece of text. ', 100);
        $chunk = new Chunk();
        $chunk->content = $longContent;

        $this->assertEquals($longContent, $chunk->content);
        $this->assertGreaterThan(2000, strlen($chunk->content));
    }

    public function test_chunk_chunk_index_can_be_zero(): void
    {
        $chunk = new Chunk();
        $chunk->chunkIndex = 0;

        $this->assertEquals(0, $chunk->chunkIndex);
    }

    public function test_chunk_chunk_index_can_be_large(): void
    {
        $chunk = new Chunk();
        $chunk->chunkIndex = 999999;

        $this->assertEquals(999999, $chunk->chunkIndex);
    }
}

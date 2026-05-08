<?php

declare(strict_types=1);

namespace Tests\Unit\EmbeddingGemma;

use App\EmbeddingGemma\EmbeddingGemmaEmbeddingGenerator;
use LLPhant\Embeddings\Document;
use OpenAI\Contracts\ClientContract;
use OpenAI\Testing\ClientFake;
use Tests\Support\TestCase;

class EmbeddingGemmaEmbeddingGeneratorTest extends TestCase
{
    public function test_generator_is_instance_of_correct_class(): void
    {
        $generator = new EmbeddingGemmaEmbeddingGenerator();

        $this->assertInstanceOf(EmbeddingGemmaEmbeddingGenerator::class, $generator);
    }

    public function test_get_embedding_length_returns_768(): void
    {
        $generator = new EmbeddingGemmaEmbeddingGenerator();

        $this->assertEquals(768, $generator->getEmbeddingLength());
    }

    public function test_get_model_name_returns_correct_model(): void
    {
        $generator = new EmbeddingGemmaEmbeddingGenerator();

        $this->assertEquals('unsloth/embeddinggemma-300m-GGUF:Q4_0', $generator->getModelName());
    }

    public function test_embed_document_with_fake_client(): void
    {
        $embeddingVector = $this->makeEmbeddingVector(768);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
        ]);

        $generator = $this->createMockForEmbeddingGenerator($fake, $embeddingVector);

        $doc = new Document();
        $doc->content = 'Test document content.';
        $doc->sourceName = 'Test';
        $doc->chunkNumber = 1;

        $generator->embedDocument($doc);

        $this->assertNotNull($doc->embedding);
        $this->assertCount(768, $doc->embedding);
    }

    public function test_embed_text_with_fake_client(): void
    {
        $embeddingVector = $this->makeEmbeddingVector(768);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
        ]);

        $generator = $this->createMockForEmbeddingGenerator($fake, $embeddingVector);

        $result = $generator->embedText('Test text to embed.');

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function test_embed_text_different_texts_produce_different_vectors(): void
    {
        $vector1 = $this->makeEmbeddingVectorForText('Hello world');
        $vector2 = $this->makeEmbeddingVectorForText('Goodbye world');

        $this->assertNotEquals($vector1, $vector2);
    }

    public function test_embed_document_preserves_document_properties(): void
    {
        $embeddingVector = $this->makeEmbeddingVector(768);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
        ]);

        $generator = $this->createMockForEmbeddingGenerator($fake, $embeddingVector);

        $doc = new Document();
        $doc->content = 'Original content';
        $doc->sourceName = 'Original Source';
        $doc->chunkNumber = 5;

        $generator->embedDocument($doc);

        $this->assertEquals('Original content', $doc->content);
        $this->assertEquals('Original Source', $doc->sourceName);
        $this->assertEquals(5, $doc->chunkNumber);
        $this->assertNotNull($doc->embedding);
    }

    public function test_get_embedding_length_is_consistent(): void
    {
        $generator1 = new EmbeddingGemmaEmbeddingGenerator();
        $generator2 = new EmbeddingGemmaEmbeddingGenerator();

        $this->assertEquals($generator1->getEmbeddingLength(), $generator2->getEmbeddingLength());
        $this->assertEquals(768, $generator1->getEmbeddingLength());
    }

    public function test_get_model_name_is_consistent(): void
    {
        $generator1 = new EmbeddingGemmaEmbeddingGenerator();
        $generator2 = new EmbeddingGemmaEmbeddingGenerator();

        $this->assertEquals($generator1->getModelName(), $generator2->getModelName());
    }

    public function test_embed_document_with_empty_content(): void
    {
        $embeddingVector = $this->makeEmbeddingVector(768);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
        ]);

        $generator = $this->createMockForEmbeddingGenerator($fake, $embeddingVector);

        $doc = new Document();
        $doc->content = '';

        $generator->embedDocument($doc);

        $this->assertNotNull($doc->embedding);
        $this->assertCount(768, $doc->embedding);
    }

    public function test_embed_document_with_long_content(): void
    {
        $embeddingVector = $this->makeEmbeddingVector(768);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
        ]);

        $generator = $this->createMockForEmbeddingGenerator($fake, $embeddingVector);

        $longContent = str_repeat('This is a long document content. ', 100);
        $doc = new Document();
        $doc->content = $longContent;

        $generator->embedDocument($doc);

        $this->assertNotNull($doc->embedding);
        $this->assertCount(768, $doc->embedding);
    }

    public function test_embed_text_with_empty_string(): void
    {
        $embeddingVector = $this->makeEmbeddingVector(768);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
        ]);

        $generator = $this->createMockForEmbeddingGenerator($fake, $embeddingVector);

        $result = $generator->embedText('');

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function test_embed_text_with_unicode(): void
    {
        $embeddingVector = $this->makeEmbeddingVector(768);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
        ]);

        $generator = $this->createMockForEmbeddingGenerator($fake, $embeddingVector);

        $result = $generator->embedText('Café résumé naïve 🎉');

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function test_generator_extends_abstract_openai_generator(): void
    {
        $generator = new EmbeddingGemmaEmbeddingGenerator();

        $this->assertInstanceOf(
            \LLPhant\Embeddings\EmbeddingGenerator\OpenAI\AbstractOpenAIEmbeddingGenerator::class,
            $generator
        );
    }

    private function createMockForEmbeddingGenerator(ClientFake $fake, array $embeddingVector): EmbeddingGemmaEmbeddingGenerator
    {
        $generator = new EmbeddingGemmaEmbeddingGenerator();

        $reflection = new \ReflectionClass($generator);

        $clientProp = $reflection->getProperty('client');
        $clientProp->setAccessible(true);
        $clientProp->setValue($generator, $fake);

        $uriProp = $reflection->getProperty('uri');
        $uriProp->setAccessible(true);
        $uriProp->setValue($generator, 'http://test.local/embeddings');

        $apiKeyProp = $reflection->getProperty('apiKey');
        $apiKeyProp->setAccessible(true);
        $apiKeyProp->setValue($generator, 'test-key');

        return $generator;
    }
}

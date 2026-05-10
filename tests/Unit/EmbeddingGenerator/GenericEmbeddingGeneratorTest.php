<?php

declare(strict_types=1);

namespace Tests\Unit\EmbeddingGenerator;

use App\EmbeddingGenerator\GenericEmbeddingGenerator;
use LLPhant\Embeddings\Document;
use LLPhant\OpenAIConfig;
use OpenAI\Contracts\ClientContract;
use OpenAI\Testing\ClientFake;
use Tests\Support\TestCase;

class GenericEmbeddingGeneratorTest extends TestCase
{
    public function testGeneratorIsInstanceOfCorrectClass(): void
    {
        $embeddingVector = $this->makeEmbeddingVector(768);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
        ]);
        $config = new OpenAIConfig();
        $config->client = $fake;
        $generator = new GenericEmbeddingGenerator($config);

        $this->assertInstanceOf(GenericEmbeddingGenerator::class, $generator);
    }

    public function testGetEmbeddingLengthReturnsDetectedLength(): void
    {
        $embeddingVector = $this->makeEmbeddingVector(768);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
        ]);
        $config = new OpenAIConfig();
        $config->client = $fake;
        $generator = new GenericEmbeddingGenerator($config);

        $this->assertEquals(768, $generator->getEmbeddingLength());
    }

    public function testGetEmbeddingLengthDetects1536(): void
    {
        $embeddingVector = $this->makeEmbeddingVector(1536);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
        ]);
        $config = new OpenAIConfig();
        $config->client = $fake;
        $generator = new GenericEmbeddingGenerator($config);

        $this->assertEquals(1536, $generator->getEmbeddingLength());
    }

    public function testGetEmbeddingLengthDetects384(): void
    {
        $embeddingVector = $this->makeEmbeddingVector(384);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
        ]);
        $config = new OpenAIConfig();
        $config->client = $fake;
        $generator = new GenericEmbeddingGenerator($config);

        $this->assertEquals(384, $generator->getEmbeddingLength());
    }

    public function testGetModelNameReturnsEnvVar(): void
    {
        putenv('EMBEDDING_MODEL=my-custom-model');
        $embeddingVector = $this->makeEmbeddingVector(768);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
        ]);
        $config = new OpenAIConfig();
        $config->client = $fake;
        $generator = new GenericEmbeddingGenerator($config);

        $this->assertEquals('my-custom-model', $generator->getModelName());

        putenv('EMBEDDING_MODEL');
    }

    public function testGetModelNameThrowsWhenNotSet(): void
    {
        putenv('EMBEDDING_MODEL');
        $embeddingVector = $this->makeEmbeddingVector(768);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
        ]);
        $config = new OpenAIConfig();
        $config->client = $fake;
        $generator = new GenericEmbeddingGenerator($config);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Environment variable 'EMBEDDING_MODEL' is not set.");

        $generator->getModelName();
    }

    public function testEmbedDocumentWithFakeClient(): void
    {
        $embeddingVector = $this->makeEmbeddingVector(768);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
            $this->createEmbeddingResponse($embeddingVector),
        ]);
        $config = new OpenAIConfig();
        $config->client = $fake;
        $generator = new GenericEmbeddingGenerator($config);

        $doc = new Document();
        $doc->content = 'Test document content.';
        $doc->sourceName = 'Test';
        $doc->chunkNumber = 1;

        $generator->embedDocument($doc);

        $this->assertNotNull($doc->embedding);
        $this->assertCount(768, $doc->embedding);
    }

    public function testEmbedTextWithFakeClient(): void
    {
        $embeddingVector = $this->makeEmbeddingVector(768);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
            $this->createEmbeddingResponse($embeddingVector),
        ]);
        $config = new OpenAIConfig();
        $config->client = $fake;
        $generator = new GenericEmbeddingGenerator($config);

        $result = $generator->embedText('Test text to embed.');

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function testGetEmbeddingLengthIsConsistent(): void
    {
        $embeddingVector = $this->makeEmbeddingVector(768);
        $fake1 = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
        ]);
        $config1 = new OpenAIConfig();
        $config1->client = $fake1;
        $generator1 = new GenericEmbeddingGenerator($config1);

        $fake2 = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
        ]);
        $config2 = new OpenAIConfig();
        $config2->client = $fake2;
        $generator2 = new GenericEmbeddingGenerator($config2);

        $this->assertEquals($generator1->getEmbeddingLength(), $generator2->getEmbeddingLength());
        $this->assertEquals(768, $generator1->getEmbeddingLength());
    }

    public function testEmbedDocumentWithEmptyContent(): void
    {
        $embeddingVector = $this->makeEmbeddingVector(768);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
            $this->createEmbeddingResponse($embeddingVector),
        ]);
        $config = new OpenAIConfig();
        $config->client = $fake;
        $generator = new GenericEmbeddingGenerator($config);

        $doc = new Document();
        $doc->content = '';

        $generator->embedDocument($doc);

        $this->assertNotNull($doc->embedding);
        $this->assertCount(768, $doc->embedding);
    }

    public function testEmbedDocumentWithLongContent(): void
    {
        $embeddingVector = $this->makeEmbeddingVector(768);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
            $this->createEmbeddingResponse($embeddingVector),
        ]);
        $config = new OpenAIConfig();
        $config->client = $fake;
        $generator = new GenericEmbeddingGenerator($config);

        $longContent = str_repeat('This is a long document content. ', 100);
        $doc = new Document();
        $doc->content = $longContent;

        $generator->embedDocument($doc);

        $this->assertNotNull($doc->embedding);
        $this->assertCount(768, $doc->embedding);
    }

    public function testEmbedTextWithUnicode(): void
    {
        $embeddingVector = $this->makeEmbeddingVector(768);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
            $this->createEmbeddingResponse($embeddingVector),
        ]);
        $config = new OpenAIConfig();
        $config->client = $fake;
        $generator = new GenericEmbeddingGenerator($config);

        $result = $generator->embedText('Café résumé naïve 🎉');

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function testGeneratorExtendsAbstractOpenaiGenerator(): void
    {
        $embeddingVector = $this->makeEmbeddingVector(768);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
        ]);
        $config = new OpenAIConfig();
        $config->client = $fake;
        $generator = new GenericEmbeddingGenerator($config);

        $this->assertInstanceOf(
            \LLPhant\Embeddings\EmbeddingGenerator\OpenAI\AbstractOpenAIEmbeddingGenerator::class,
            $generator
        );
    }

    public function testEmbedTextDifferentLengths(): void
    {
        $vector384 = $this->makeEmbeddingVector(384);
        $fake1 = new ClientFake([
            $this->createEmbeddingResponse($vector384),
            $this->createEmbeddingResponse($vector384),
        ]);
        $config1 = new OpenAIConfig();
        $config1->client = $fake1;
        $generator1 = new GenericEmbeddingGenerator($config1);

        $this->assertEquals(384, $generator1->getEmbeddingLength());

        $vector1536 = $this->makeEmbeddingVector(1536);
        $fake2 = new ClientFake([
            $this->createEmbeddingResponse($vector1536),
            $this->createEmbeddingResponse($vector1536),
        ]);
        $config2 = new OpenAIConfig();
        $config2->client = $fake2;
        $generator2 = new GenericEmbeddingGenerator($config2);

        $this->assertEquals(1536, $generator2->getEmbeddingLength());
    }

    public function testDetectsCorrectLengthFor1024(): void
    {
        $embeddingVector = $this->makeEmbeddingVector(1024);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
        ]);
        $config = new OpenAIConfig();
        $config->client = $fake;
        $generator = new GenericEmbeddingGenerator($config);

        $this->assertEquals(1024, $generator->getEmbeddingLength());
    }
}

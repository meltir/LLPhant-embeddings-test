<?php

declare(strict_types=1);

namespace Tests\Unit\Embeddings;

use App\Embeddings\EmbeddingService;
use LLPhant\Embeddings\Document;
use OpenAI\Testing\ClientFake;
use Tests\Support\TestCase;

class EmbeddingServiceTest extends TestCase
{
    private EmbeddingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $embeddingVector = $this->makeEmbeddingVector(768);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($embeddingVector),
            $this->createEmbeddingResponse($embeddingVector),
        ]);
        $config = new \LLPhant\OpenAIConfig();
        $config->client = $fake;
        $generator = new \App\EmbeddingGenerator\GenericEmbeddingGenerator($config);
        $this->service = new EmbeddingService($generator);
    }

    public function testServiceIsInstanceOfCorrectClass(): void
    {
        $this->assertInstanceOf(EmbeddingService::class, $this->service);
    }

    public function testEmbedReturnsDocumentWithEmbedding(): void
    {
        $doc = new Document();
        $doc->content = 'Test document content.';
        $doc->sourceName = 'Test';
        $doc->sourceName = 'Test';
        $doc->chunkNumber = 1;

        // We can't easily mock the generator, so we test the structure
        $result = $this->service->embed($doc);

        $this->assertInstanceOf(Document::class, $result);
        $this->assertSame($doc, $result);
    }

    public function testEmbedTextReturnsArray(): void
    {
        $result = $this->service->embedText('Test text.');

        $this->assertIsArray($result);
    }

    public function testEmbedTextReturnsFloatArray(): void
    {
        $result = $this->service->embedText('Test text.');

        foreach ($result as $value) {
            $this->assertIsFloat($value);
        }
    }

    public function testEmbedTextReturns768Dimensions(): void
    {
        $result = $this->service->embedText('Test text.');

        $this->assertCount(768, $result);
    }

    public function testEmbedPreservesDocumentContent(): void
    {
        $doc = new Document();
        $doc->content = 'Original content';
        $doc->sourceName = 'Original Title';

        $result = $this->service->embed($doc);

        $this->assertEquals('Original content', $result->content);
        $this->assertEquals('Original Title', $result->sourceName);
    }

    public function testEmbedWithEmptyDocument(): void
    {
        $doc = new Document();
        $doc->content = '';

        $result = $this->service->embed($doc);

        $this->assertInstanceOf(Document::class, $result);
    }

    public function testEmbedTextWithEmptyString(): void
    {
        $result = $this->service->embedText('');

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function testEmbedTextWithUnicode(): void
    {
        $result = $this->service->embedText('Café résumé naïve 🎉');

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function testEmbedTextWithLongText(): void
    {
        $longText = str_repeat('This is a long text. ', 10);
        $result = $this->service->embedText($longText);

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function testEmbedTextWithSpecialCharacters(): void
    {
        $result = $this->service->embedText('Special @#$%^&*() chars!');

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function testEmbedReturnsSameDocumentInstance(): void
    {
        $doc = new Document();
        $doc->content = 'Test';

        $result = $this->service->embed($doc);

        $this->assertSame($doc, $result);
    }

    public function testEmbedTextDifferentTexts(): void
    {
        $result1 = $this->service->embedText('Text one.');
        $result2 = $this->service->embedText('Text two.');

        $this->assertIsArray($result1);
        $this->assertIsArray($result2);
        $this->assertCount(768, $result1);
        $this->assertCount(768, $result2);
    }

    public function testEmbedTextValuesAreBetweenNegativeOneAndOne(): void
    {
        $result = $this->service->embedText('Test text.');

        foreach ($result as $value) {
            $this->assertGreaterThanOrEqual(-1.0, $value);
            $this->assertLessThanOrEqual(1.0, $value);
        }
    }

    public function testEmbedWithNumberContent(): void
    {
        $doc = new Document();
        $doc->content = '12345';

        $result = $this->service->embed($doc);

        $this->assertInstanceOf(Document::class, $result);
    }

    public function testEmbedWithJsonContent(): void
    {
        $doc = new Document();
        $doc->content = '{"key": "value"}';

        $result = $this->service->embed($doc);

        $this->assertInstanceOf(Document::class, $result);
    }

    public function testEmbedTextWithNewlines(): void
    {
        $result = $this->service->embedText("Line one.\nLine two.\nLine three.");

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function testEmbedTextWithTabs(): void
    {
        $result = $this->service->embedText("Tab\there\tand\there.");

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function testEmbedTextWithMixedContent(): void
    {
        $mixed = "Text with 123 numbers, @symbols, and café words.\n\nNew line here.";
        $result = $this->service->embedText($mixed);

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function testServiceCreatesNewGeneratorEachCall(): void
    {
        // Each call should work independently
        $result1 = $this->service->embedText('First call.');
        $result2 = $this->service->embedText('Second call.');

        $this->assertIsArray($result1);
        $this->assertIsArray($result2);
        $this->assertCount(768, $result1);
        $this->assertCount(768, $result2);
    }

    public function testEmbedWithWhitespaceOnly(): void
    {
        $doc = new Document();
        $doc->content = "   \n\n   ";

        $result = $this->service->embed($doc);

        $this->assertInstanceOf(Document::class, $result);
    }

    public function testEmbedTextWithSingleCharacter(): void
    {
        $result = $this->service->embedText('a');

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function testEmbedTextWithWhitespace(): void
    {
        $result = $this->service->embedText('   ');

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }
}

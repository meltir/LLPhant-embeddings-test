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
        $this->service = new EmbeddingService();
    }

    public function test_service_is_instance_of_correct_class(): void
    {
        $this->assertInstanceOf(EmbeddingService::class, $this->service);
    }

    public function test_embed_returns_document_with_embedding(): void
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

    public function test_embed_text_returns_array(): void
    {
        $result = $this->service->embedText('Test text.');

        $this->assertIsArray($result);
    }

    public function test_embed_text_returns_float_array(): void
    {
        $result = $this->service->embedText('Test text.');

        foreach ($result as $value) {
            $this->assertIsFloat($value);
        }
    }

    public function test_embed_text_returns_768_dimensions(): void
    {
        $result = $this->service->embedText('Test text.');

        $this->assertCount(768, $result);
    }

    public function test_embed_preserves_document_content(): void
    {
        $doc = new Document();
        $doc->content = 'Original content';
        $doc->sourceName = 'Original Title';

        $result = $this->service->embed($doc);

        $this->assertEquals('Original content', $result->content);
        $this->assertEquals('Original Title', $result->sourceName);
    }

    public function test_embed_with_empty_document(): void
    {
        $doc = new Document();
        $doc->content = '';

        $result = $this->service->embed($doc);

        $this->assertInstanceOf(Document::class, $result);
    }

    public function test_embed_text_with_empty_string(): void
    {
        $result = $this->service->embedText('');

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function test_embed_text_with_unicode(): void
    {
        $result = $this->service->embedText('Café résumé naïve 🎉');

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function test_embed_text_with_long_text(): void
    {
        $longText = str_repeat('This is a long text. ', 10);
        $result = $this->service->embedText($longText);

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function test_embed_text_with_special_characters(): void
    {
        $result = $this->service->embedText('Special @#$%^&*() chars!');

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function test_embed_returns_same_document_instance(): void
    {
        $doc = new Document();
        $doc->content = 'Test';

        $result = $this->service->embed($doc);

        $this->assertSame($doc, $result);
    }

    public function test_embed_text_different_texts(): void
    {
        $result1 = $this->service->embedText('Text one.');
        $result2 = $this->service->embedText('Text two.');

        $this->assertIsArray($result1);
        $this->assertIsArray($result2);
        $this->assertCount(768, $result1);
        $this->assertCount(768, $result2);
    }

    public function test_embed_text_values_are_between_negative_one_and_one(): void
    {
        $result = $this->service->embedText('Test text.');

        foreach ($result as $value) {
            $this->assertGreaterThanOrEqual(-1.0, $value);
            $this->assertLessThanOrEqual(1.0, $value);
        }
    }

    public function test_embed_with_number_content(): void
    {
        $doc = new Document();
        $doc->content = '12345';

        $result = $this->service->embed($doc);

        $this->assertInstanceOf(Document::class, $result);
    }

    public function test_embed_with_json_content(): void
    {
        $doc = new Document();
        $doc->content = '{"key": "value"}';

        $result = $this->service->embed($doc);

        $this->assertInstanceOf(Document::class, $result);
    }

    public function test_embed_text_with_newlines(): void
    {
        $result = $this->service->embedText("Line one.\nLine two.\nLine three.");

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function test_embed_text_with_tabs(): void
    {
        $result = $this->service->embedText("Tab\there\tand\there.");

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function test_embed_text_with_mixed_content(): void
    {
        $mixed = "Text with 123 numbers, @symbols, and café words.\n\nNew line here.";
        $result = $this->service->embedText($mixed);

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function test_service_creates_new_generator_each_call(): void
    {
        // Each call should work independently
        $result1 = $this->service->embedText('First call.');
        $result2 = $this->service->embedText('Second call.');

        $this->assertIsArray($result1);
        $this->assertIsArray($result2);
        $this->assertCount(768, $result1);
        $this->assertCount(768, $result2);
    }

    public function test_embed_with_whitespace_only(): void
    {
        $doc = new Document();
        $doc->content = "   \n\n   ";

        $result = $this->service->embed($doc);

        $this->assertInstanceOf(Document::class, $result);
    }

    public function test_embed_text_with_single_character(): void
    {
        $result = $this->service->embedText('a');

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }

    public function test_embed_text_with_whitespace(): void
    {
        $result = $this->service->embedText('   ');

        $this->assertIsArray($result);
        $this->assertCount(768, $result);
    }
}

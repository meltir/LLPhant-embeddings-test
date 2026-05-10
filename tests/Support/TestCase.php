<?php

declare(strict_types=1);

namespace Tests\Support;

use LLPhant\Embeddings\Document;
use OpenAI\Responses\Chat\CreateResponse;
use OpenAI\Responses\Embeddings\CreateResponse as EmbeddingCreateResponse;
use OpenAI\Testing\ClientFake;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    protected ClientFake $fake;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('EMBEDDING_MODEL=test-embedding-model');
        $this->fake = new ClientFake();
    }

    protected function createChatResponse(string $content, string $model = 'gpt-3.5-turbo'): CreateResponse
    {
        return CreateResponse::fake([
            'model' => $model,
            'choices' => [[
                'message' => ['content' => $content],
            ]],
        ]);
    }

    protected function createEmbeddingResponse(array $embedding): EmbeddingCreateResponse
    {
        return EmbeddingCreateResponse::fake([
            'data' => [[
                'embedding' => $embedding,
            ]],
        ]);
    }

    protected function makeEmbeddingVector(int $length = 768): array
    {
        $vector = [];
        for ($i = 0; $i < $length; $i++) {
            $vector[] = round(sin($i * 0.01) * 0.5, 6);
        }

        return $vector;
    }

    protected function makeEmbeddingVectorForText(string $text, int $length = 768): array
    {
        $hash = crc32($text);
        $vector = [];
        for ($i = 0; $i < $length; $i++) {
            $value = (($hash >> ($i % 32)) & 1) ? 0.5 : -0.5;
            $vector[] = round($value + sin($i * 0.1) * 0.1, 6);
        }

        return $vector;
    }

    protected function createDocument(string $content, string $title = 'Test Document', int $chunkNumber = 1): Document
    {
        $doc = new Document();
        $doc->content = $content;
        $doc->sourceName = $title;
        $doc->chunkNumber = $chunkNumber;

        return $doc;
    }
}

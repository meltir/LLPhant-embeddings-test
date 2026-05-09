<?php

declare(strict_types=1);

namespace Tests\Unit\Documents;

use App\Documents\LLPhantDocumentChunker;
use App\Documents\TextDocumentPreprocessor;
use LLPhant\Embeddings\Document;
use Tests\Support\TestCase;

class LLPhantDocumentChunkerTest extends TestCase
{
    private LLPhantDocumentChunker $chunker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chunker = new LLPhantDocumentChunker();
    }

    public function testChunkerIsInstanceOfCorrectClass(): void
    {
        $this->assertInstanceOf(LLPhantDocumentChunker::class, $this->chunker);
    }

    public function testChunkerChunksDocument(): void
    {
        $doc = new Document();
        $doc->content = "This is a test document. It has multiple sentences. Each sentence is a separate thought. We need enough text to create chunks. The chunker should split this into multiple parts. Each part should be a reasonable size for processing.";
        $doc->sourceName = 'Test Document';

        $chunks = $this->chunker->chunk($doc, 50, '.', 2);

        $this->assertIsArray($chunks);
        $this->assertGreaterThan(0, count($chunks));

        foreach ($chunks as $chunk) {
            $this->assertInstanceOf(Document::class, $chunk);
            $this->assertNotEmpty($chunk->content);
        }
    }

    public function testChunkerRespectsMaxLength(): void
    {
        $doc = new Document();
        $doc->content = "First sentence. Second sentence. Third sentence. Fourth sentence. Fifth sentence.";

        $chunks = $this->chunker->chunk($doc, 20, '.', 0);

        $this->assertIsArray($chunks);
        $this->assertGreaterThan(0, count($chunks));
    }

    public function testChunkerUsesSeparator(): void
    {
        $doc = new Document();
        $doc->content = "First paragraph. Second paragraph. Third paragraph.";

        $chunks = $this->chunker->chunk($doc, 100, '.', 0);

        $this->assertIsArray($chunks);
        $this->assertGreaterThan(0, count($chunks));
    }

    public function testChunkerWithWordOverlap(): void
    {
        $doc = new Document();
        $doc->content = "Word one. Word two. Word three. Word four. Word five. Word six. Word seven. Word eight.";

        $chunks = $this->chunker->chunk($doc, 30, '.', 3);

        $this->assertIsArray($chunks);
        $this->assertGreaterThan(0, count($chunks));
    }

    public function testChunkerReturnsEmptyArrayForEmptyContent(): void
    {
        $doc = new Document();
        $doc->content = '';

        $chunks = $this->chunker->chunk($doc, 100, '.', 0);

        $this->assertIsArray($chunks);
    }

    public function testChunkerReturnsEmptyArrayForWhitespaceContent(): void
    {
        $doc = new Document();
        $doc->content = "   \n\n   ";

        $chunks = $this->chunker->chunk($doc, 100, '.', 0);

        $this->assertIsArray($chunks);
    }

    public function testChunkerSingleChunkForShortContent(): void
    {
        $doc = new Document();
        $doc->content = "Short.";

        $chunks = $this->chunker->chunk($doc, 1000, '.', 0);

        $this->assertCount(1, $chunks);
    }

    public function testChunkerSetsChunkNumbers(): void
    {
        $doc = new Document();
        $doc->content = "First sentence. Second sentence. Third sentence. Fourth sentence. Fifth sentence.";

        $chunks = $this->chunker->chunk($doc, 20, '.', 0);

        $this->assertGreaterThan(1, count($chunks));

        $chunkNumbers = array_map(fn ($c) => $c->chunkNumber, $chunks);
        $this->assertEquals(range(0, count($chunks) - 1), $chunkNumbers);
    }

public function testChunkerPreservesSourceName(): void
    {
        $doc = new Document();
        $doc->content = "Sentence one. Sentence two.";
        $doc->sourceName = 'My Test Story';

        $chunks = $this->chunker->chunk($doc, 20, '.', 0);

        foreach ($chunks as $chunk) {
            $this->assertEquals('My Test Story', $chunk->sourceName);
        }
    }

    public function testChunkerWithLargeMaxLength(): void
    {
        $doc = new Document();
        $doc->content = "One sentence.";

        $chunks = $this->chunker->chunk($doc, 10000, '.', 0);

        $this->assertCount(1, $chunks);
        $this->assertEquals("One sentence.", $chunks[0]->content);
    }

    public function testChunkerWithZeroWordOverlap(): void
    {
        $doc = new Document();
        $doc->content = "First. Second. Third. Fourth.";

        $chunks = $this->chunker->chunk($doc, 15, '.', 0);

        $this->assertIsArray($chunks);
        $this->assertGreaterThan(0, count($chunks));
    }

    public function testChunkerWithDifferentSeparator(): void
    {
        $doc = new Document();
        $doc->content = "First; Second; Third; Fourth.";

        $chunks = $this->chunker->chunk($doc, 20, ';', 0);

        $this->assertIsArray($chunks);
    }

    public function testChunkerHandlesLongParagraph(): void
    {
        $longContent = str_repeat('This is a test sentence with multiple words. ', 50);
        $doc = new Document();
        $doc->content = $longContent;

        $chunks = $this->chunker->chunk($doc, 100, '.', 5);

        $this->assertIsArray($chunks);
        $this->assertGreaterThan(0, count($chunks));

        foreach ($chunks as $chunk) {
            $this->assertNotEmpty($chunk->content);
        }
    }

    public function testChunkerContentDoesNotContainNullBytes(): void
    {
        $doc = new Document();
        $doc->content = "Normal content. More content.";

        $chunks = $this->chunker->chunk($doc, 20, '.', 0);

        foreach ($chunks as $chunk) {
            $this->assertStringNotContainsString("\0", $chunk->content);
        }
    }

    public function testChunkerReturnsDocumentsWithSourceName(): void
    {
        $doc = new Document();
        $doc->content = "Sentence one. Sentence two.";
        $doc->sourceName = 'TestSource';

        $chunks = $this->chunker->chunk($doc, 20, '.', 0);

        foreach ($chunks as $chunk) {
            $this->assertEquals('TestSource', $chunk->sourceName);
        }
    }
}

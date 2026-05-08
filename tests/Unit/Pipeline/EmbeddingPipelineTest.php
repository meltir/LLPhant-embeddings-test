<?php

declare(strict_types=1);

namespace Tests\Unit\Pipeline;

use App\Documents\FileDocumentReader;
use App\Documents\LLPhantDocumentChunker;
use App\Documents\TextDocumentPreprocessor;
use App\Infrastructure\ChunkStorage;
use App\Pipeline\EmbeddingPipeline;
use App\Pipeline\EmbeddingStats;
use LLPhant\Embeddings\Document;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\Support\TestCase;

class EmbeddingPipelineTest extends TestCase
{
    private MockObject&FileDocumentReader $documentReader;
    private MockObject&TextDocumentPreprocessor $documentPreprocessor;
    private MockObject&LLPhantDocumentChunker $documentChunker;
    private MockObject&ChunkStorage $chunkStorage;
    private MockObject&OutputInterface $output;
    private MockObject&LoggerInterface $logger;
    private EmbeddingPipeline $pipeline;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentReader = $this->createMock(FileDocumentReader::class);
        $this->documentPreprocessor = $this->createMock(TextDocumentPreprocessor::class);
        $this->documentChunker = $this->createMock(LLPhantDocumentChunker::class);
        $this->chunkStorage = $this->createMock(ChunkStorage::class);
        $this->output = $this->createMock(OutputInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->pipeline = new EmbeddingPipeline(
            $this->documentReader,
            $this->documentPreprocessor,
            $this->documentChunker,
            $this->chunkStorage,
            $this->output,
            $this->logger
        );
    }

    public function test_pipeline_is_instance_of_correct_class(): void
    {
        $this->assertInstanceOf(EmbeddingPipeline::class, $this->pipeline);
    }

    public function test_pipeline_constructor_parameters(): void
    {
        $reader = $this->createMock(FileDocumentReader::class);
        $preprocessor = $this->createMock(TextDocumentPreprocessor::class);
        $chunker = $this->createMock(LLPhantDocumentChunker::class);
        $storage = $this->createMock(ChunkStorage::class);
        $output = $this->createMock(OutputInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $pipeline = new EmbeddingPipeline(
            $reader,
            $preprocessor,
            $chunker,
            $storage,
            $output,
            $logger
        );

        $this->assertInstanceOf(EmbeddingPipeline::class, $pipeline);
    }

    public function test_run_with_empty_directory(): void
    {
        $this->documentReader->method('read')->willReturn([]);
        $this->chunkStorage->method('count')->willReturn(0);

        $this->output->expects($this->atLeastOnce())
            ->method('writeln');

        $this->pipeline->run(sys_get_temp_dir());

        $this->assertTrue(true);
    }

    public function test_run_processes_documents(): void
    {
        $doc = new Document();
        $doc->content = "The Adventure of Test\n\nThis is test content.";

        $processedDoc = new Document();
        $processedDoc->content = 'The Adventure of Test\n\nThis is test content.';

        $chunk = new Document();
        $chunk->content = 'This is test content.';
        $chunk->sourceName = 'The Adventure of Test';
        $chunk->chunkNumber = 1;

        $this->documentReader->method('read')->willReturn([$doc]);
        $this->documentPreprocessor->method('preprocess')->willReturn($processedDoc);
        $this->documentChunker->method('chunk')->willReturn([$chunk]);
        $this->chunkStorage->method('exists')->willReturn(false);
        $this->chunkStorage->method('persist')->willReturnCallback(function () {
        });
        $this->chunkStorage->method('count')->willReturn(1);

        $this->documentReader->expects($this->once())
            ->method('read');

        $this->documentPreprocessor->expects($this->atLeastOnce())
            ->method('preprocess');

        $this->documentChunker->expects($this->atLeastOnce())
            ->method('chunk');

        $this->chunkStorage->expects($this->once())
            ->method('exists');

        $this->chunkStorage->expects($this->once())
            ->method('persist');

        $this->chunkStorage->expects($this->atLeast(1))
            ->method('count');

        $this->pipeline->run(sys_get_temp_dir());

        $this->assertTrue(true);
    }

    public function test_run_skips_existing_chunks(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nContent.";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nContent.';

        $chunk = new Document();
        $chunk->content = 'Content.';
        $chunk->sourceName = 'Title';
        $chunk->chunkNumber = 1;

        $this->documentReader->method('read')->willReturn([$doc]);
        $this->documentPreprocessor->method('preprocess')->willReturn($processedDoc);
        $this->documentChunker->method('chunk')->willReturn([$chunk]);
        $this->chunkStorage->method('exists')->willReturn(true);
        $this->chunkStorage->method('count')->willReturn(0);

        $this->chunkStorage->expects($this->once())
            ->method('exists')
            ->with('Title', 1, 'Content.')
            ->willReturn(true);

        $this->chunkStorage->expects($this->never())
            ->method('persist');

        $this->pipeline->run(sys_get_temp_dir());

        $this->assertTrue(true);
    }

    public function test_run_with_custom_parameters(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nContent.";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nContent.';

        $chunk = new Document();
        $chunk->content = 'Content.';
        $chunk->sourceName = 'Title';
        $chunk->chunkNumber = 1;

        $this->documentReader->method('read')->willReturn([$doc]);
        $this->documentPreprocessor->method('preprocess')->willReturn($processedDoc);
        $this->documentChunker->method('chunk')->willReturn([$chunk]);
        $this->chunkStorage->method('exists')->willReturn(false);
        $this->chunkStorage->method('persist')->willReturnCallback(function () {
        });
        $this->chunkStorage->method('count')->willReturn(1);

        $this->documentChunker->expects($this->atLeastOnce())
            ->method('chunk')
            ->with($this->anything(), 150, ';', 5);

        $this->pipeline->run(sys_get_temp_dir(), 150, ';', 5);

        $this->assertTrue(true);
    }

    public function test_run_with_custom_embedding_generator(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nContent.";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nContent.';

        $chunk = new Document();
        $chunk->content = 'Content.';
        $chunk->sourceName = 'Title';
        $chunk->chunkNumber = 1;

        $this->documentReader->method('read')->willReturn([$doc]);
        $this->documentPreprocessor->method('preprocess')->willReturn($processedDoc);
        $this->documentChunker->method('chunk')->willReturn([$chunk]);
        $this->chunkStorage->method('exists')->willReturn(false);
        $this->chunkStorage->method('persist')->willReturnCallback(function () {
        });
        $this->chunkStorage->method('count')->willReturn(1);

        $this->chunkStorage->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Document::class), 'Title', 1);

        $this->pipeline->run(
            sys_get_temp_dir(),
            200,
            '.',
            10,
            \App\EmbeddingGemma\EmbeddingGemmaEmbeddingGenerator::class
        );

        $this->assertTrue(true);
    }

    public function test_run_clears_entity_manager_every_100_inserts(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nContent.";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nContent.';

        $chunks = [];
        for ($i = 1; $i <= 150; $i++) {
            $chunk = new Document();
            $chunk->content = "Content $i";
            $chunk->sourceName = 'Title';
            $chunk->chunkNumber = $i;
            $chunks[] = $chunk;
        }

        $this->documentReader->method('read')->willReturn([$doc]);
        $this->documentPreprocessor->method('preprocess')->willReturn($processedDoc);
        $this->documentChunker->method('chunk')->willReturn($chunks);
        $this->chunkStorage->method('exists')->willReturn(false);
        $this->chunkStorage->method('persist')->willReturnCallback(function () {
        });
        $this->chunkStorage->method('count')->willReturn(150);

        $this->chunkStorage->expects($this->atLeast(1))
            ->method('clear');

        $this->pipeline->run(sys_get_temp_dir());

        $this->assertTrue(true);
    }

    public function test_run_with_all_chunks_existing(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nContent.";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nContent.';

        $chunk = new Document();
        $chunk->content = 'Content.';
        $chunk->sourceName = 'Title';
        $chunk->chunkNumber = 1;

        $this->documentReader->method('read')->willReturn([$doc]);
        $this->documentPreprocessor->method('preprocess')->willReturn($processedDoc);
        $this->documentChunker->method('chunk')->willReturn([$chunk]);
        $this->chunkStorage->method('exists')->willReturn(true);
        $this->chunkStorage->method('count')->willReturn(0);

        $this->chunkStorage->expects($this->once())
            ->method('exists')
            ->willReturn(true);

        $this->chunkStorage->expects($this->never())
            ->method('persist');

        $this->pipeline->run(sys_get_temp_dir());

        $this->assertTrue(true);
    }

    public function test_run_with_mixed_existing_and_new_chunks(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nContent.";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nContent.';

        $chunk1 = new Document();
        $chunk1->content = 'Existing content.';
        $chunk1->sourceName = 'Title';
        $chunk1->chunkNumber = 1;

        $chunk2 = new Document();
        $chunk2->content = 'New content.';
        $chunk2->sourceName = 'Title';
        $chunk2->chunkNumber = 2;

        $this->documentReader->method('read')->willReturn([$doc]);
        $this->documentPreprocessor->method('preprocess')->willReturn($processedDoc);
        $this->documentChunker->method('chunk')->willReturn([$chunk1, $chunk2]);
        $this->chunkStorage->method('exists')
            ->willReturnOnConsecutiveCalls(true, false);
        $this->chunkStorage->method('persist')->willReturnCallback(function () {
        });
        $this->chunkStorage->method('count')->willReturn(1);

        $this->chunkStorage->expects($this->exactly(2))
            ->method('exists');

        $this->chunkStorage->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Document::class), 'Title', 2);

        $this->pipeline->run(sys_get_temp_dir());

        $this->assertTrue(true);
    }

    public function test_run_with_unicode_document(): void
    {
        $doc = new Document();
        $doc->content = "Café Story\n\nCafé résumé naïve content.";

        $processedDoc = new Document();
        $processedDoc->content = 'Café Story\n\nCafé résumé naïve content.';

        $chunk = new Document();
        $chunk->content = 'Café résumé naïve content.';
        $chunk->sourceName = 'Café Story';
        $chunk->chunkNumber = 1;

        $this->documentReader->method('read')->willReturn([$doc]);
        $this->documentPreprocessor->method('preprocess')->willReturn($processedDoc);
        $this->documentChunker->method('chunk')->willReturn([$chunk]);
        $this->chunkStorage->method('exists')->willReturn(false);
        $this->chunkStorage->method('persist')->willReturnCallback(function () {
        });
        $this->chunkStorage->method('count')->willReturn(1);

        $this->chunkStorage->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Document::class), 'Café Story', 1);

        $this->pipeline->run(sys_get_temp_dir());

        $this->assertTrue(true);
    }

    public function test_run_with_single_chunk(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nShort.";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nShort.';

        $chunk = new Document();
        $chunk->content = 'Short.';
        $chunk->sourceName = 'Title';
        $chunk->chunkNumber = 1;

        $this->documentReader->method('read')->willReturn([$doc]);
        $this->documentPreprocessor->method('preprocess')->willReturn($processedDoc);
        $this->documentChunker->method('chunk')->willReturn([$chunk]);
        $this->chunkStorage->method('exists')->willReturn(false);
        $this->chunkStorage->method('persist')->willReturnCallback(function () {
        });
        $this->chunkStorage->method('count')->willReturn(1);

        $this->chunkStorage->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Document::class), 'Title', 1);

        $this->pipeline->run(sys_get_temp_dir());

        $this->assertTrue(true);
    }

    public function test_run_with_many_chunks_per_document(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nContent.";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nContent.';

        $chunks = [];
        for ($i = 1; $i <= 20; $i++) {
            $chunk = new Document();
            $chunk->content = "Chunk $i content.";
            $chunk->sourceName = 'Title';
            $chunk->chunkNumber = $i;
            $chunks[] = $chunk;
        }

        $this->documentReader->method('read')->willReturn([$doc]);
        $this->documentPreprocessor->method('preprocess')->willReturn($processedDoc);
        $this->documentChunker->method('chunk')->willReturn($chunks);
        $this->chunkStorage->method('exists')->willReturn(false);
        $this->chunkStorage->method('persist')->willReturnCallback(function () {
        });
        $this->chunkStorage->method('count')->willReturn(20);

        $this->chunkStorage->expects($this->exactly(20))
            ->method('persist');

        $this->pipeline->run(sys_get_temp_dir());

        $this->assertTrue(true);
    }

    public function test_run_with_special_characters_in_content(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nSpecial @#\$%^&*() chars!";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nSpecial @#\$%^&*() chars!';

        $chunk = new Document();
        $chunk->content = 'Special @#\$%^&*() chars!';
        $chunk->sourceName = 'Title';
        $chunk->chunkNumber = 1;

        $this->documentReader->method('read')->willReturn([$doc]);
        $this->documentPreprocessor->method('preprocess')->willReturn($processedDoc);
        $this->documentChunker->method('chunk')->willReturn([$chunk]);
        $this->chunkStorage->method('exists')->willReturn(false);
        $this->chunkStorage->method('persist')->willReturnCallback(function () {
        });
        $this->chunkStorage->method('count')->willReturn(1);

        $this->chunkStorage->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Document::class), 'Title', 1);

        $this->pipeline->run(sys_get_temp_dir());

        $this->assertTrue(true);
    }

    public function test_run_with_empty_chunks(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nContent.";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nContent.';

        $this->documentReader->method('read')->willReturn([$doc]);
        $this->documentPreprocessor->method('preprocess')->willReturn($processedDoc);
        $this->documentChunker->method('chunk')->willReturn([]);
        $this->chunkStorage->method('count')->willReturn(0);

        $this->pipeline->run(sys_get_temp_dir());

        $this->assertTrue(true);
    }

    public function test_run_with_chunk_index_zero(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nContent.";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nContent.';

        $chunk = new Document();
        $chunk->content = 'Content.';
        $chunk->sourceName = 'Title';
        $chunk->chunkNumber = 0;

        $this->documentReader->method('read')->willReturn([$doc]);
        $this->documentPreprocessor->method('preprocess')->willReturn($processedDoc);
        $this->documentChunker->method('chunk')->willReturn([$chunk]);
        $this->chunkStorage->method('exists')->willReturn(false);
        $this->chunkStorage->method('persist')->willReturnCallback(function () {
        });
        $this->chunkStorage->method('count')->willReturn(1);

        $this->chunkStorage->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Document::class), 'Title', 0);

        $this->pipeline->run(sys_get_temp_dir());

        $this->assertTrue(true);
    }

    public function test_run_with_large_chunk_index(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nContent.";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nContent.';

        $chunk = new Document();
        $chunk->content = 'Content.';
        $chunk->sourceName = 'Title';
        $chunk->chunkNumber = 999999;

        $this->documentReader->method('read')->willReturn([$doc]);
        $this->documentPreprocessor->method('preprocess')->willReturn($processedDoc);
        $this->documentChunker->method('chunk')->willReturn([$chunk]);
        $this->chunkStorage->method('exists')->willReturn(false);
        $this->chunkStorage->method('persist')->willReturnCallback(function () {
        });
        $this->chunkStorage->method('count')->willReturn(1);

        $this->chunkStorage->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Document::class), 'Title', 999999);

        $this->pipeline->run(sys_get_temp_dir());

        $this->assertTrue(true);
    }

    public function test_run_with_multiple_documents(): void
    {
        $doc1 = new Document();
        $doc1->content = "Story One\n\nContent one.";
        $doc2 = new Document();
        $doc2->content = "Story Two\n\nContent two.";

        $processedDoc1 = new Document();
        $processedDoc1->content = 'Story One\n\nContent one.';
        $processedDoc2 = new Document();
        $processedDoc2->content = 'Story Two\n\nContent two.';

        $chunk1 = new Document();
        $chunk1->content = 'Content one.';
        $chunk1->sourceName = 'Story One';
        $chunk1->chunkNumber = 1;

        $chunk2 = new Document();
        $chunk2->content = 'Content two.';
        $chunk2->sourceName = 'Story Two';
        $chunk2->chunkNumber = 1;

        $this->documentReader->method('read')->willReturn([$doc1, $doc2]);
        $this->documentPreprocessor->method('preprocess')
            ->willReturnOnConsecutiveCalls($processedDoc1, $processedDoc2, $processedDoc1, $processedDoc2);
        $this->documentChunker->method('chunk')
            ->willReturnOnConsecutiveCalls([$chunk1], [$chunk2], [$chunk1], [$chunk2]);
        $this->chunkStorage->method('exists')->willReturn(false);
        $this->chunkStorage->method('persist')->willReturnCallback(function () {
        });
        $this->chunkStorage->method('count')->willReturn(2);

        $this->documentReader->expects($this->once())
            ->method('read');

        $this->documentPreprocessor->expects($this->exactly(4))
            ->method('preprocess');

        $this->documentChunker->expects($this->exactly(4))
            ->method('chunk');

        $this->chunkStorage->expects($this->exactly(2))
            ->method('exists');

        $this->chunkStorage->expects($this->exactly(2))
            ->method('persist');

        $this->pipeline->run(sys_get_temp_dir());

        $this->assertTrue(true);
    }
}

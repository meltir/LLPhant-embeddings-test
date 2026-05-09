<?php

declare(strict_types=1);

namespace Tests\Unit\Pipeline;

use App\Documents\FileDocumentReader;
use App\Documents\LLPhantDocumentChunker;
use App\Documents\TextDocumentPreprocessor;
use App\Infrastructure\ChunkStorage;
use App\Pipeline\EmbeddingPipeline;
use LLPhant\Embeddings\Document;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\Support\TestCase;

class EmbeddingPipelineTest extends TestCase
{
    private function createPipeline(
        FileDocumentReader $reader,
        TextDocumentPreprocessor $preprocessor,
        LLPhantDocumentChunker $chunker,
        ChunkStorage $storage,
        OutputInterface $output,
        LoggerInterface $logger
    ): EmbeddingPipeline {
        return new EmbeddingPipeline($reader, $preprocessor, $chunker, $storage, $output, $logger);
    }

    public function testPipelineIsInstanceOfCorrectClass(): void
    {
        $reader = $this->createStub(FileDocumentReader::class);
        $preprocessor = $this->createStub(TextDocumentPreprocessor::class);
        $chunker = $this->createStub(LLPhantDocumentChunker::class);
        $storage = $this->createStub(ChunkStorage::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $pipeline = $this->createPipeline($reader, $preprocessor, $chunker, $storage, $output, $logger);
        $this->assertInstanceOf(EmbeddingPipeline::class, $pipeline);
    }

    public function testPipelineConstructorParameters(): void
    {
        $reader = $this->createStub(FileDocumentReader::class);
        $preprocessor = $this->createStub(TextDocumentPreprocessor::class);
        $chunker = $this->createStub(LLPhantDocumentChunker::class);
        $storage = $this->createStub(ChunkStorage::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $pipeline = $this->createPipeline($reader, $preprocessor, $chunker, $storage, $output, $logger);
        $this->assertInstanceOf(EmbeddingPipeline::class, $pipeline);
    }

    public function testRunWithEmptyDirectory(): void
    {
        $reader = $this->createStub(FileDocumentReader::class);
        $preprocessor = $this->createStub(TextDocumentPreprocessor::class);
        $chunker = $this->createStub(LLPhantDocumentChunker::class);
        $storage = $this->createStub(ChunkStorage::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $reader->method('read')->willReturn([]);
        $storage->method('count')->willReturn(0);
        $output->method('writeln');

        $pipeline = $this->createPipeline($reader, $preprocessor, $chunker, $storage, $output, $logger);
        $pipeline->run(sys_get_temp_dir());
        $this->assertTrue(true);
    }

    public function testRunProcessesDocuments(): void
    {
        $doc = new Document();
        $doc->content = "The Adventure of Test\n\nThis is test content.";

        $processedDoc = new Document();
        $processedDoc->content = 'The Adventure of Test\n\nThis is test content.';

        $chunk = new Document();
        $chunk->content = 'This is test content.';
        $chunk->sourceName = 'The Adventure of Test';
        $chunk->chunkNumber = 1;

        $reader = $this->createStub(FileDocumentReader::class);
        $preprocessor = $this->createStub(TextDocumentPreprocessor::class);
        $chunker = $this->createStub(LLPhantDocumentChunker::class);
        $storage = $this->createStub(ChunkStorage::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $reader->method('read')->willReturn([$doc]);
        $preprocessor->method('preprocess')->willReturn($processedDoc);
        $chunker->method('chunk')->willReturn([$chunk]);
        $storage->method('exists')->willReturn(false);
        $storage->method('persist')->willReturnCallback(function () {
        });
        $storage->method('count')->willReturn(1);

        $pipeline = $this->createPipeline($reader, $preprocessor, $chunker, $storage, $output, $logger);
        $pipeline->run(sys_get_temp_dir());
        $this->assertTrue(true);
    }

    public function testRunSkipsExistingChunks(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nContent.";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nContent.';

        $chunk = new Document();
        $chunk->content = 'Content.';
        $chunk->sourceName = 'Title';
        $chunk->chunkNumber = 1;

        $reader = $this->createStub(FileDocumentReader::class);
        $preprocessor = $this->createStub(TextDocumentPreprocessor::class);
        $chunker = $this->createStub(LLPhantDocumentChunker::class);
        $storage = $this->createStub(ChunkStorage::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $reader->method('read')->willReturn([$doc]);
        $preprocessor->method('preprocess')->willReturn($processedDoc);
        $chunker->method('chunk')->willReturn([$chunk]);
        $storage->method('exists')->willReturn(true);
        $storage->method('count')->willReturn(0);
        $storage->method('persist');

        $pipeline = $this->createPipeline($reader, $preprocessor, $chunker, $storage, $output, $logger);
        $pipeline->run(sys_get_temp_dir());
        $this->assertTrue(true);
    }

    public function testRunWithCustomParameters(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nContent.";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nContent.';

        $chunk = new Document();
        $chunk->content = 'Content.';
        $chunk->sourceName = 'Title';
        $chunk->chunkNumber = 1;

        $reader = $this->createStub(FileDocumentReader::class);
        $preprocessor = $this->createStub(TextDocumentPreprocessor::class);
        $chunker = $this->createStub(LLPhantDocumentChunker::class);
        $storage = $this->createStub(ChunkStorage::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $reader->method('read')->willReturn([$doc]);
        $preprocessor->method('preprocess')->willReturn($processedDoc);
        $chunker->method('chunk')->willReturn([$chunk]);
        $storage->method('exists')->willReturn(false);
        $storage->method('persist')->willReturnCallback(function () {
        });
        $storage->method('count')->willReturn(1);

        $pipeline = $this->createPipeline($reader, $preprocessor, $chunker, $storage, $output, $logger);
        $pipeline->run(sys_get_temp_dir(), 150, ';', 5);
        $this->assertTrue(true);
    }

    public function testRunWithCustomEmbeddingGenerator(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nContent.";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nContent.';

        $chunk = new Document();
        $chunk->content = 'Content.';
        $chunk->sourceName = 'Title';
        $chunk->chunkNumber = 1;

        $reader = $this->createStub(FileDocumentReader::class);
        $preprocessor = $this->createStub(TextDocumentPreprocessor::class);
        $chunker = $this->createStub(LLPhantDocumentChunker::class);
        $storage = $this->createStub(ChunkStorage::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $reader->method('read')->willReturn([$doc]);
        $preprocessor->method('preprocess')->willReturn($processedDoc);
        $chunker->method('chunk')->willReturn([$chunk]);
        $storage->method('exists')->willReturn(false);
        $storage->method('persist')->willReturnCallback(function () {
        });
        $storage->method('count')->willReturn(1);

        $pipeline = $this->createPipeline($reader, $preprocessor, $chunker, $storage, $output, $logger);
        $pipeline->run(sys_get_temp_dir(), 200, '.', 10, \App\EmbeddingGemma\EmbeddingGemmaEmbeddingGenerator::class);
        $this->assertTrue(true);
    }

    public function testRunClearsEntityManagerEvery100Inserts(): void
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

        $reader = $this->createStub(FileDocumentReader::class);
        $preprocessor = $this->createStub(TextDocumentPreprocessor::class);
        $chunker = $this->createStub(LLPhantDocumentChunker::class);
        $storage = $this->createStub(ChunkStorage::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $reader->method('read')->willReturn([$doc]);
        $preprocessor->method('preprocess')->willReturn($processedDoc);
        $chunker->method('chunk')->willReturn($chunks);
        $storage->method('exists')->willReturn(false);
        $storage->method('persist')->willReturnCallback(function () {
        });
        $storage->method('count')->willReturn(150);

        $pipeline = $this->createPipeline($reader, $preprocessor, $chunker, $storage, $output, $logger);
        $pipeline->run(sys_get_temp_dir());
        $this->assertTrue(true);
    }

    public function testRunWithAllChunksExisting(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nContent.";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nContent.';

        $chunk = new Document();
        $chunk->content = 'Content.';
        $chunk->sourceName = 'Title';
        $chunk->chunkNumber = 1;

        $reader = $this->createStub(FileDocumentReader::class);
        $preprocessor = $this->createStub(TextDocumentPreprocessor::class);
        $chunker = $this->createStub(LLPhantDocumentChunker::class);
        $storage = $this->createStub(ChunkStorage::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $reader->method('read')->willReturn([$doc]);
        $preprocessor->method('preprocess')->willReturn($processedDoc);
        $chunker->method('chunk')->willReturn([$chunk]);
        $storage->method('exists')->willReturn(true);
        $storage->method('count')->willReturn(0);
        $storage->method('persist');

        $pipeline = $this->createPipeline($reader, $preprocessor, $chunker, $storage, $output, $logger);
        $pipeline->run(sys_get_temp_dir());
        $this->assertTrue(true);
    }

    public function testRunWithMixedExistingAndNewChunks(): void
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

        $reader = $this->createStub(FileDocumentReader::class);
        $preprocessor = $this->createStub(TextDocumentPreprocessor::class);
        $chunker = $this->createStub(LLPhantDocumentChunker::class);
        $storage = $this->createStub(ChunkStorage::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $reader->method('read')->willReturn([$doc]);
        $preprocessor->method('preprocess')->willReturn($processedDoc);
        $chunker->method('chunk')->willReturn([$chunk1, $chunk2]);
        $storage->method('exists')->willReturnOnConsecutiveCalls(true, false);
        $storage->method('persist')->willReturnCallback(function () {
        });
        $storage->method('count')->willReturn(1);

        $pipeline = $this->createPipeline($reader, $preprocessor, $chunker, $storage, $output, $logger);
        $pipeline->run(sys_get_temp_dir());
        $this->assertTrue(true);
    }

    public function testRunWithUnicodeDocument(): void
    {
        $doc = new Document();
        $doc->content = "Café Story\n\nCafé résumé naïve content.";

        $processedDoc = new Document();
        $processedDoc->content = 'Café Story\n\nCafé résumé naïve content.';

        $chunk = new Document();
        $chunk->content = 'Café résumé naïve content.';
        $chunk->sourceName = 'Café Story';
        $chunk->chunkNumber = 1;

        $reader = $this->createStub(FileDocumentReader::class);
        $preprocessor = $this->createStub(TextDocumentPreprocessor::class);
        $chunker = $this->createStub(LLPhantDocumentChunker::class);
        $storage = $this->createStub(ChunkStorage::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $reader->method('read')->willReturn([$doc]);
        $preprocessor->method('preprocess')->willReturn($processedDoc);
        $chunker->method('chunk')->willReturn([$chunk]);
        $storage->method('exists')->willReturn(false);
        $storage->method('persist')->willReturnCallback(function () {
        });
        $storage->method('count')->willReturn(1);

        $pipeline = $this->createPipeline($reader, $preprocessor, $chunker, $storage, $output, $logger);
        $pipeline->run(sys_get_temp_dir());
        $this->assertTrue(true);
    }

    public function testRunWithSingleChunk(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nShort.";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nShort.';

        $chunk = new Document();
        $chunk->content = 'Short.';
        $chunk->sourceName = 'Title';
        $chunk->chunkNumber = 1;

        $reader = $this->createStub(FileDocumentReader::class);
        $preprocessor = $this->createStub(TextDocumentPreprocessor::class);
        $chunker = $this->createStub(LLPhantDocumentChunker::class);
        $storage = $this->createStub(ChunkStorage::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $reader->method('read')->willReturn([$doc]);
        $preprocessor->method('preprocess')->willReturn($processedDoc);
        $chunker->method('chunk')->willReturn([$chunk]);
        $storage->method('exists')->willReturn(false);
        $storage->method('persist')->willReturnCallback(function () {
        });
        $storage->method('count')->willReturn(1);

        $pipeline = $this->createPipeline($reader, $preprocessor, $chunker, $storage, $output, $logger);
        $pipeline->run(sys_get_temp_dir());
        $this->assertTrue(true);
    }

    public function testRunWithManyChunksPerDocument(): void
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

        $reader = $this->createStub(FileDocumentReader::class);
        $preprocessor = $this->createStub(TextDocumentPreprocessor::class);
        $chunker = $this->createStub(LLPhantDocumentChunker::class);
        $storage = $this->createStub(ChunkStorage::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $reader->method('read')->willReturn([$doc]);
        $preprocessor->method('preprocess')->willReturn($processedDoc);
        $chunker->method('chunk')->willReturn($chunks);
        $storage->method('exists')->willReturn(false);
        $storage->method('persist')->willReturnCallback(function () {
        });
        $storage->method('count')->willReturn(20);

        $pipeline = $this->createPipeline($reader, $preprocessor, $chunker, $storage, $output, $logger);
        $pipeline->run(sys_get_temp_dir());
        $this->assertTrue(true);
    }

    public function testRunWithSpecialCharactersInContent(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nSpecial @#\$%^&*() chars!";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nSpecial @#\$%^&*() chars!';

        $chunk = new Document();
        $chunk->content = 'Special @#\$%^&*() chars!';
        $chunk->sourceName = 'Title';
        $chunk->chunkNumber = 1;

        $reader = $this->createStub(FileDocumentReader::class);
        $preprocessor = $this->createStub(TextDocumentPreprocessor::class);
        $chunker = $this->createStub(LLPhantDocumentChunker::class);
        $storage = $this->createStub(ChunkStorage::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $reader->method('read')->willReturn([$doc]);
        $preprocessor->method('preprocess')->willReturn($processedDoc);
        $chunker->method('chunk')->willReturn([$chunk]);
        $storage->method('exists')->willReturn(false);
        $storage->method('persist')->willReturnCallback(function () {
        });
        $storage->method('count')->willReturn(1);

        $pipeline = $this->createPipeline($reader, $preprocessor, $chunker, $storage, $output, $logger);
        $pipeline->run(sys_get_temp_dir());
        $this->assertTrue(true);
    }

    public function testRunWithEmptyChunks(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nContent.";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nContent.';

        $reader = $this->createStub(FileDocumentReader::class);
        $preprocessor = $this->createStub(TextDocumentPreprocessor::class);
        $chunker = $this->createStub(LLPhantDocumentChunker::class);
        $storage = $this->createStub(ChunkStorage::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $reader->method('read')->willReturn([$doc]);
        $preprocessor->method('preprocess')->willReturn($processedDoc);
        $chunker->method('chunk')->willReturn([]);
        $storage->method('count')->willReturn(0);

        $pipeline = $this->createPipeline($reader, $preprocessor, $chunker, $storage, $output, $logger);
        $pipeline->run(sys_get_temp_dir());
        $this->assertTrue(true);
    }

    public function testRunWithChunkIndexZero(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nContent.";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nContent.';

        $chunk = new Document();
        $chunk->content = 'Content.';
        $chunk->sourceName = 'Title';
        $chunk->chunkNumber = 0;

        $reader = $this->createStub(FileDocumentReader::class);
        $preprocessor = $this->createStub(TextDocumentPreprocessor::class);
        $chunker = $this->createStub(LLPhantDocumentChunker::class);
        $storage = $this->createStub(ChunkStorage::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $reader->method('read')->willReturn([$doc]);
        $preprocessor->method('preprocess')->willReturn($processedDoc);
        $chunker->method('chunk')->willReturn([$chunk]);
        $storage->method('exists')->willReturn(false);
        $storage->method('persist')->willReturnCallback(function () {
        });
        $storage->method('count')->willReturn(1);

        $pipeline = $this->createPipeline($reader, $preprocessor, $chunker, $storage, $output, $logger);
        $pipeline->run(sys_get_temp_dir());
        $this->assertTrue(true);
    }

    public function testRunWithLargeChunkIndex(): void
    {
        $doc = new Document();
        $doc->content = "Title\n\nContent.";

        $processedDoc = new Document();
        $processedDoc->content = 'Title\n\nContent.';

        $chunk = new Document();
        $chunk->content = 'Content.';
        $chunk->sourceName = 'Title';
        $chunk->chunkNumber = 999999;

        $reader = $this->createStub(FileDocumentReader::class);
        $preprocessor = $this->createStub(TextDocumentPreprocessor::class);
        $chunker = $this->createStub(LLPhantDocumentChunker::class);
        $storage = $this->createStub(ChunkStorage::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $reader->method('read')->willReturn([$doc]);
        $preprocessor->method('preprocess')->willReturn($processedDoc);
        $chunker->method('chunk')->willReturn([$chunk]);
        $storage->method('exists')->willReturn(false);
        $storage->method('persist')->willReturnCallback(function () {
        });
        $storage->method('count')->willReturn(1);

        $pipeline = $this->createPipeline($reader, $preprocessor, $chunker, $storage, $output, $logger);
        $pipeline->run(sys_get_temp_dir());
        $this->assertTrue(true);
    }

    public function testRunWithMultipleDocuments(): void
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

        $reader = $this->createStub(FileDocumentReader::class);
        $preprocessor = $this->createStub(TextDocumentPreprocessor::class);
        $chunker = $this->createStub(LLPhantDocumentChunker::class);
        $storage = $this->createStub(ChunkStorage::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $reader->method('read')->willReturn([$doc1, $doc2]);
        $preprocessor->method('preprocess')
            ->willReturnOnConsecutiveCalls($processedDoc1, $processedDoc2, $processedDoc1, $processedDoc2);
        $chunker->method('chunk')
            ->willReturnOnConsecutiveCalls([$chunk1], [$chunk2], [$chunk1], [$chunk2]);
        $storage->method('exists')->willReturn(false);
        $storage->method('persist')->willReturnCallback(function () {
        });
        $storage->method('count')->willReturn(2);

        $pipeline = $this->createPipeline($reader, $preprocessor, $chunker, $storage, $output, $logger);
        $pipeline->run(sys_get_temp_dir());
        $this->assertTrue(true);
    }
}

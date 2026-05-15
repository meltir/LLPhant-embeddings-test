<?php

declare(strict_types=1);

namespace App\Pipeline;

use LLPhant\Embeddings\Document;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use App\Documents\TextDocumentPreprocessor;
use App\Documents\LLPhantDocumentChunker;
use App\Documents\FileDocumentReader;
use App\Infrastructure\ChunkStorage;

class EmbeddingPipeline
{
    public function __construct(
        private readonly FileDocumentReader $documentReader,
        private readonly TextDocumentPreprocessor $documentPreprocessor,
        private readonly LLPhantDocumentChunker $documentChunker,
        private readonly ChunkStorage $chunkStorage,
        private readonly OutputInterface $output,
        private readonly LoggerInterface $logger,
        private readonly ?\App\EmbeddingGenerator\GenericEmbeddingGenerator $embeddingGenerator = null,
    ) {
    }

    public function run(string $textDir, int $maxLength = 200, string $separator = '.', int $wordOverlap = 10): void
    {
      $stats = new EmbeddingStats();

        $documents = $this->documentReader->read($textDir);
        $this->output->writeln("Found <info>" . count($documents) . "</info> document(s).\n");
        $this->logger->info('Documents loaded', ['count' => count($documents)]);

        $generator = $this->embeddingGenerator ?? new \App\EmbeddingGenerator\GenericEmbeddingGenerator();

         $totalChunks = 0;
        $filePaths = [];
        $textDirBasename = basename($textDir);
        foreach ($documents as $i => $doc) {
            $title = $this->extractTitle($doc);
            $filePaths[$i] = $textDirBasename . '/' . ($doc->sourceName ?? $title);
            $doc = $this->documentPreprocessor->preprocess($doc, $title);
            $splitDocs = $this->documentChunker->chunk($doc, $maxLength, $separator, $wordOverlap);
            $totalChunks += count($splitDocs);
        }

        if ($totalChunks > 0) {
            $progressBar = new ProgressBar($this->output, $totalChunks);
            $progressBar->setFormat(
                ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %message:50s%'
            );
            $progressBar->start();

            $currentDoc = 0;
            foreach ($documents as $i => $doc) {
                $currentDoc++;
                $title = $this->extractTitle($doc);
                $this->logger->debug('Processing document', ['title' => $title]);

                $filePath = $filePaths[$i];

                $doc = $this->documentPreprocessor->preprocess($doc, $title);

                $splitDocs = $this->documentChunker->chunk($doc, $maxLength, $separator, $wordOverlap);
                $this->logger->debug('Document chunked', ['title' => $title, 'chunks' => count($splitDocs)]);

                $progressBar->setMessage("{$filePath}: " . count($splitDocs) . " chunks");

                foreach ($splitDocs as $chunkDoc) {
                    $stats->total++;
                    $progressBar->setMessage("{$filePath}: chunk {$chunkDoc->chunkNumber}");

                    if (
                        $this->chunkStorage->exists(
                            $chunkDoc->sourceName,
                            $chunkDoc->chunkNumber,
                            $chunkDoc->content
                        )
                    ) {
                        $stats->skipped++;
                        $progressBar->advance();
                        $this->logger->debug('Chunk already exists, skipping', [
                            'source' => $chunkDoc->sourceName,
                            'index' => $chunkDoc->chunkNumber,
                        ]);
                        continue;
                    }

                    $generator->embedDocument($chunkDoc);

                    $this->chunkStorage->persist($chunkDoc, $chunkDoc->sourceName, $chunkDoc->chunkNumber);
                    $stats->inserted++;
                    $progressBar->advance();

                    if ($stats->inserted % 100 === 0) {
                        $this->chunkStorage->clear();
                        $this->logger->debug('EntityManager cleared for memory management');
                    }
                }

                unset($splitDocs);
            }

            $progressBar->finish();
            $this->output->writeln('');
        }

        $this->output->writeln("\n<info>=== Embedding generation complete ===</info>");
        $this->output->writeln(
            "Total chunks: <info>{$stats->total}</info>, "
            . "Inserted: <info>{$stats->inserted}</info>, "
            . "Skipped: <info>{$stats->skipped}</info>"
        );
        $this->output->writeln("Total chunks in database: <info>{$this->chunkStorage->count()}</info>");
        $this->logger->info('Embedding generation complete', [
            'total' => $stats->total,
            'inserted' => $stats->inserted,
            'skipped' => $stats->skipped,
            'in_database' => $this->chunkStorage->count(),
        ]);
    }

    private function extractTitle(Document $doc): string
    {
        $lines = explode("\n", $doc->content);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return 'Unknown';
    }
}

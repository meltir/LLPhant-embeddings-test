<?php

declare(strict_types=1);

namespace App\Console;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Documents\FileDocumentReader;
use App\Documents\TextDocumentPreprocessor;
use App\Documents\LLPhantDocumentChunker;
use App\Infrastructure\DatabaseConnection;
use App\Infrastructure\ChunkStorage;
use App\Pipeline\EmbeddingPipeline;
use App\Logger\ConsoleLogger;
use LLPhant\Embeddings\VectorStores\Doctrine\DoctrineVectorStore;

#[AsCommand(
    name: 'app:embeddings:generate',
    description: 'Generate embeddings for all documents in the text directory',
)]
class EmbeddingGenerateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('text-dir', null, InputOption::VALUE_OPTIONAL, 'Directory containing text files', __DIR__ . '/../../text')
            ->addOption('max-length', null, InputOption::VALUE_OPTIONAL, 'Maximum chunk length', '200')
            ->addOption('separator', null, InputOption::VALUE_OPTIONAL, 'Chunk separator', '.')
            ->addOption('word-overlap', null, InputOption::VALUE_OPTIONAL, 'Word overlap between chunks', '10')
            ->addOption('embedding-generator', null, InputOption::VALUE_OPTIONAL, 'Embedding generator class name', \App\EmbeddingGemma\EmbeddingGemmaEmbeddingGenerator::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $textDir = (string) $input->getOption('text-dir');
        $maxLength = (int) $input->getOption('max-length');
        $separator = (string) $input->getOption('separator');
        $wordOverlap = (int) $input->getOption('word-overlap');
        $embeddingGeneratorClass = (string) $input->getOption('embedding-generator');

        $output->writeln('<info>=== Sherlock Holmes Embedding Generator ===</info>');

        $connection = new DatabaseConnection();
        $entityManager = $connection->create();

        $vectorStore = new DoctrineVectorStore($entityManager, \App\Entity\Chunk::class);
        $chunkStorage = new ChunkStorage($entityManager, $vectorStore);

        $documentReader = new FileDocumentReader();
        $documentPreprocessor = new TextDocumentPreprocessor();
        $documentChunker = new LLPhantDocumentChunker();

        $logger = ConsoleLogger::create($output);
        $pipeline = new EmbeddingPipeline(
            $documentReader,
            $documentPreprocessor,
            $documentChunker,
            $chunkStorage,
            $output,
            $logger
        );

        $pipeline->run($textDir, $maxLength, $separator, $wordOverlap, $embeddingGeneratorClass);

        return Command::SUCCESS;
    }
}

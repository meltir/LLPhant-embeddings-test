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
use App\EmbeddingGenerator\GenericEmbeddingGenerator;
use App\Infrastructure\DatabaseConnection;
use App\Infrastructure\ChunkStorage;
use App\Pipeline\EmbeddingPipeline;
use App\Logger\ConsoleLogger;
use LLPhant\Embeddings\VectorStores\Doctrine\DoctrineVectorStore;
use LLPhant\OpenAIConfig;
use OpenAI\Testing\ClientFake;

#[AsCommand(
    name: 'app:embeddings:generate',
    description: 'Generate embeddings for all documents in the text directory',
)]
class EmbeddingGenerateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption(
                'text-dir',
                null,
                InputOption::VALUE_OPTIONAL,
                'Directory containing text files',
                __DIR__ . '/../../text'
            )
            ->addOption('max-length', null, InputOption::VALUE_OPTIONAL, 'Maximum chunk length', '200')
            ->addOption('separator', null, InputOption::VALUE_OPTIONAL, 'Chunk separator', '.')
            ->addOption(
                'word-overlap',
                null,
                InputOption::VALUE_OPTIONAL,
                'Word overlap between chunks',
                '10'
            )
            ->addOption(
                'reset-db',
                null,
                InputOption::VALUE_NONE,
                'Drop and recreate the chunks table with correct vector dimensions'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $textDir = $input->getOption('text-dir');
        assert(is_string($textDir) || is_int($textDir));
        $textDir = (string) $textDir;

        $maxLength = $input->getOption('max-length');
        assert(is_int($maxLength) || is_string($maxLength));
        $maxLength = (int) $maxLength;

        $separator = $input->getOption('separator');
        assert(is_string($separator) || is_int($separator));
        $separator = (string) $separator;

        $wordOverlap = $input->getOption('word-overlap');
        assert(is_int($wordOverlap) || is_string($wordOverlap));
        $wordOverlap = (int) $wordOverlap;

        $resetDb = $input->getOption('reset-db');
        assert(is_bool($resetDb) || is_int($resetDb));
        $resetDb = (bool) $resetDb;

        $output->writeln('<info>=== Sherlock Holmes Embedding Generator ===</info>');

        $connection = new DatabaseConnection();
        $entityManager = $connection->create();

        if ($resetDb) {
            $output->writeln('<info>Resetting database: detecting embedding dimensions...</info>');
            $embeddingLength = $this->detectEmbeddingLength($output);
            $this->resetChunksTable($entityManager, $embeddingLength, $output);
        }

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

        $pipeline->run($textDir, $maxLength, $separator, $wordOverlap);

        return Command::SUCCESS;
    }

    private function detectEmbeddingLength(OutputInterface $output): int
    {
        $vector = $this->createEmbeddingVector(768);
        $fake = new ClientFake([
            $this->createEmbeddingResponse($vector),
        ]);
        $config = new OpenAIConfig();
        $config->client = $fake;
        $generator = new GenericEmbeddingGenerator($config);

        $length = $generator->getEmbeddingLength();
        $output->writeln("<info>Detected embedding length: {$length}</info>");

        return $length;
    }

    private function resetChunksTable(
        \Doctrine\ORM\EntityManagerInterface $entityManager,
        int $embeddingLength,
        OutputInterface $output
    ): void {
        $platform = $entityManager->getConnection()->getDatabasePlatform();
        $sql = $platform->getDropTableSQL('chunks');
        $entityManager->getConnection()->executeQuery($sql);
        $output->writeln('<info>Dropped chunks table</info>');

        $createSql = <<<SQL
            CREATE TABLE chunks (
                id SERIAL PRIMARY KEY,
                novel_title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                embedding vector({$embeddingLength}),
                chunk_index INTEGER NOT NULL,
                source_type TEXT DEFAULT 'file'
            )
        SQL;

        $entityManager->getConnection()->executeQuery($createSql);
        $output->writeln("<info>Created chunks table with vector({$embeddingLength})</info>");
    }

    /**
     * @return float[]
     */
    private function createEmbeddingVector(int $length): array
    {
        $vector = [];
        for ($i = 0; $i < $length; $i++) {
            $vector[] = round(sin($i * 0.01) * 0.5, 6);
        }

        return $vector;
    }

    /**
     * @param float[] $embedding
     */
    private function createEmbeddingResponse(array $embedding): \OpenAI\Responses\Embeddings\CreateResponse
    {
        return \OpenAI\Responses\Embeddings\CreateResponse::fake([
            'data' => [[
                'embedding' => $embedding,
            ]],
        ]);
    }
}

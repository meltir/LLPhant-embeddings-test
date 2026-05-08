<?php

declare(strict_types=1);

namespace App\Console;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Chat\ChatSession;
use App\Chat\RagPipeline;
use App\Chat\QueryRefiner;
use App\Chat\ContextAssessor;
use App\Chat\AnswerGenerator;
use App\Search\VectorSearchService;
use App\Infrastructure\DatabaseConnection;
use App\Infrastructure\LlmChatClient;
use App\Logger\ConsoleLogger;

#[AsCommand(
    name: 'app:chat',
    description: 'Start the Sherlock Holmes RAG chatbot',
)]
class ChatCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $chatModel = getenv("CHAT_MODEL") ?: "unsloth/Qwen3.6-35B-A3B-GGUF:Q4_K_XL";

        $connection = new DatabaseConnection();
        $entityManager = $connection->create();

        $chatClient = new LlmChatClient(
            \OpenAI::factory()
                ->withApiKey(getenv('LLM_API_KEY') ?: 'sk-not-needed')
                ->withBaseUri(getenv('LLM_URL') ?: 'http://192.168.1.20:8001/v1')
                ->make(),
            $chatModel
        );

        $queryRefiner = new QueryRefiner($chatClient);
        $contextAssessor = new ContextAssessor($chatClient);
        $answerGenerator = new AnswerGenerator($chatClient);

        $vectorSearch = new VectorSearchService($entityManager, \App\Entity\Chunk::class);

        $ragPipeline = new RagPipeline(
            $vectorSearch,
            $queryRefiner,
            $contextAssessor,
            $answerGenerator
        );

        $logger = ConsoleLogger::create($output);
        $session = new ChatSession($ragPipeline, $output, $logger);
        $session->run();

        return Command::SUCCESS;
    }
}

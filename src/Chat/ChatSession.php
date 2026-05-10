<?php

declare(strict_types=1);

namespace App\Chat;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChatSession
{
    public function __construct(
        private readonly RagPipeline $ragPipeline,
        private readonly OutputInterface $output,
        private readonly LoggerInterface $logger
    ) {
    }

    public function run(): void
    {
        $this->output->writeln([
            '<info>=== Sherlock Holmes RAG Chatbot ===</info>',
            'Type your question about the stories, or <comment>quit</comment> to exit.',
            '',
        ]);

        while (true) {
            $this->output->write('<question>> </question>');
            $input = trim(fgets(STDIN) ?: '');

            if ($input === '' || strtolower($input) === 'quit' || strtolower($input) === 'exit') {
                $this->output->writeln('Goodbye!');
                $this->logger->info('Chat session ended');
                break;
            }

            $result = $this->ragPipeline->ask($input);

            $this->output->writeln(['', '<info>Refining query...</info>']);
            $this->output->writeln('  Refined to: <comment>"' . $result['refinedQuery'] . '"</comment>');
            $this->output->writeln('  Generating embedding...');
            $this->logger->debug('Query refined', ['original' => $input, 'refined' => $result['refinedQuery']]);

            $k = $result['expanded'] ? 12 : 4;
            $this->output->writeln("  Searching vector store (k={$k})...");
            $this->logger->debug('Vector search performed', ['k' => $k, 'expanded' => $result['expanded']]);

            if ($result['expanded']) {
                $this->output->writeln('  Not enough context, expanding search...');
                $this->logger->info('Context insufficient, expanding search');
            }

            $this->output->writeln('  Generating answer...');
            $this->output->writeln([
                '', '<info>--- Answer ---</info>', $result['answer'], '--------------', '',
            ]);
            $this->logger->debug('Answer generated', [
                'question' => $input,
                'answer_length' => strlen($result['answer']),
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Chat;

use LLPhant\Chat\Message;
use App\Interfaces\IContextAssessor;
use App\Infrastructure\LlmChatClient;

class ContextAssessor implements IContextAssessor
{
    public function __construct(
        private readonly LlmChatClient $chatClient
    ) {
    }

    /**
     * @return 'ENOUGH'|'NOT_ENOUGH'
     */
    public function assess(string $question, string $context): string
    {
        $messages = [
            Message::system(
                "You are a context evaluator. Given a question and retrieved text passages, determine if "
                . "the passages contain enough information to answer the question. Reply with exactly 'ENOUGH' "
                . "if the context suffices, or 'NOT_ENOUGH' if more context is needed. Nothing else."
            ),
            Message::user("Question: {$question}\n\nRetrieved context:\n{$context}"),
        ];

        $result = $this->chatClient->chat($messages);

        $normalized = strtoupper(trim($result));

        return match ($normalized) {
            'ENOUGH' => 'ENOUGH',
            'NOT_ENOUGH' => 'NOT_ENOUGH',
            default => 'NOT_ENOUGH',
        };
    }
}

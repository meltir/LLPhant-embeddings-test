<?php

declare(strict_types=1);

namespace App\Chat;

use LLPhant\Chat\Message;
use App\Interfaces\IAnswerGenerator;
use App\Infrastructure\LlmChatClient;

class AnswerGenerator implements IAnswerGenerator
{
    public function __construct(
        private readonly LlmChatClient $chatClient
    ) {
    }

    public function generate(string $question, string $context): string
    {
        $messages = [
            Message::system(
                "You are a knowledgeable assistant about the Sherlock Holmes stories by Arthur Conan Doyle. "
                . "Answer the user's question based on the provided text passages from the stories. "
                . "If the passages contain sufficient information, give a direct answer. "
                . "If not, say so honestly. Cite which story the information comes from when possible."
            ),
            Message::user("Question: {$question}\n\nRelevant passages:\n{$context}"),
        ];

        return $this->chatClient->chat($messages);
    }
}

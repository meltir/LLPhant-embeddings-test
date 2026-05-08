<?php

declare(strict_types=1);

namespace App\Chat;

use LLPhant\Chat\Message;
use App\Interfaces\IQueryRefiner;
use App\Infrastructure\LlmChatClient;

class QueryRefiner implements IQueryRefiner
{
    public function __construct(
        private readonly LlmChatClient $chatClient
    ) {
    }

    public function refine(string $question): string
    {
        $messages = [
            Message::system(
                "You are a search query refiner. Given a user's question about the Sherlock Holmes stories, "
                . "condense it into a short, focused search query (max 15 words) that captures the essential "
                . "keywords and concepts. The query should be SHORTER or the same length as the original question. "
                . "Remove unnecessary words and do NOT include 'Sherlock Holmes' or 'Holmes' in the query, "
                . "as all documents are from the Sherlock Holmes collection. Keep only key entities, actions, and "
                . "story-specific details. Output ONLY the refined query, nothing else."
            ),
            Message::user("Original question: {$question}"),
        ];

        $result = $this->chatClient->chat($messages);

        return trim($result);
    }
}

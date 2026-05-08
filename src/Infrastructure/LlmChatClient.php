<?php

declare(strict_types=1);

namespace App\Infrastructure;

use LLPhant\Chat\Message;

class LlmChatClient
{
    public function __construct(
        private readonly \OpenAI\Contracts\ClientContract $client,
        private readonly string $model
    ) {
    }

    /**
     * @param Message[] $messages
     */
    public function chat(array $messages): string
    {
        $response = $this->client->chat()->create([
            'model' => $this->model,
            'messages' => array_map(static function (Message $msg): array {
                return ['role' => $msg->role, 'content' => $msg->content];
            }, $messages),
        ]);

        return $response->choices[0]->message->content ?? '';
    }
}

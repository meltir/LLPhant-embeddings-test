<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use App\Infrastructure\LlmChatClient;
use LLPhant\Chat\Message;
use OpenAI\Contracts\ClientContract;
use OpenAI\Contracts\Resources\ChatContract;
use OpenAI\Responses\Chat\CreateResponse;
use OpenAI\Testing\ClientFake;
use Tests\Support\TestCase;

class LlmChatClientTest extends TestCase
{
    private LlmChatClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $chatClient = new \OpenAI\Testing\ClientFake([$this->createChatResponse('Test response')]);
        $this->client = new LlmChatClient($chatClient, 'test-model');
    }

    public function testClientIsInstanceOfCorrectClass(): void
    {
        $this->assertInstanceOf(LlmChatClient::class, $this->client);
    }

    public function testChatReturnsResponse(): void
    {
        $messages = [
            Message::user('Hello'),
        ];

        $response = $this->client->chat($messages);

        $this->assertEquals('Test response', $response);
    }

    public function testChatWithMultipleMessages(): void
    {
        $messages = [
            Message::system('You are a helpful assistant.'),
            Message::user('What is 2+2?'),
            Message::assistant('4'),
            Message::user('And 3+3?'),
        ];

        $fake = new ClientFake([$this->createChatResponse('6')]);
        $testClient = new LlmChatClient($fake, 'test-model');

        $response = $testClient->chat($messages);

        $this->assertEquals('6', $response);
    }

    public function testChatWithEmptyMessages(): void
    {
        $messages = [];

        $fake = new ClientFake([$this->createChatResponse('Empty response')]);
        $testClient = new LlmChatClient($fake, 'test-model');

        $response = $testClient->chat($messages);

        $this->assertEquals('Empty response', $response);
    }

    public function testChatWithLongMessage(): void
    {
        $longContent = str_repeat('This is a long message. ', 100);
        $messages = [Message::user($longContent)];

        $fake = new ClientFake([$this->createChatResponse('Short reply')]);
        $testClient = new LlmChatClient($fake, 'test-model');

        $response = $testClient->chat($messages);

        $this->assertEquals('Short reply', $response);
    }

    public function testChatWithUnicodeMessage(): void
    {
        $messages = [Message::user('Café résumé naïve.')];

        $fake = new ClientFake([$this->createChatResponse('Réponse en français.')]);
        $testClient = new LlmChatClient($fake, 'test-model');

        $response = $testClient->chat($messages);

        $this->assertEquals('Réponse en français.', $response);
    }

    public function testChatWithSpecialCharacters(): void
    {
        $messages = [Message::user('Special chars: @#$%^&*()!')];

        $fake = new ClientFake([$this->createChatResponse('Got it!')]);
        $testClient = new LlmChatClient($fake, 'test-model');

        $response = $testClient->chat($messages);

        $this->assertEquals('Got it!', $response);
    }

    public function testChatUsesCorrectModel(): void
    {
        $fake = new ClientFake([$this->createChatResponse('Response', 'custom-model')]);
        $testClient = new LlmChatClient($fake, 'custom-model');

        $response = $testClient->chat([Message::user('Test')]);

        $this->assertEquals('Response', $response);
    }

    public function testChatResponseContainsOnlyContent(): void
    {
        $responseContent = 'This is the answer content.';
        $fake = new ClientFake([$this->createChatResponse($responseContent)]);
        $testClient = new LlmChatClient($fake, 'test-model');

        $result = $testClient->chat([Message::user('Question')]);

        $this->assertEquals($responseContent, $result);
        $this->assertNotEquals('chatcmpl-123', $result);
    }

    public function testChatWithToolCallMessage(): void
    {
        $messages = [
            Message::user('Search for information.'),
        ];

        $fake = new ClientFake([$this->createChatResponse('Search results here.')]);
        $testClient = new LlmChatClient($fake, 'test-model');

        $response = $testClient->chat($messages);

        $this->assertEquals('Search results here.', $response);
    }

    public function testClientUsesFakeClientResponses(): void
    {
        $fake = new ClientFake([
            $this->createChatResponse('First response'),
            $this->createChatResponse('Second response'),
        ]);
        $testClient = new LlmChatClient($fake, 'test-model');

        $response1 = $testClient->chat([Message::user('First')]);
        $response2 = $testClient->chat([Message::user('Second')]);

        $this->assertEquals('First response', $response1);
        $this->assertEquals('Second response', $response2);
    }

    public function testChatWithWhitespaceResponse(): void
    {
        $fake = new ClientFake([$this->createChatResponse('   ')]);
        $testClient = new LlmChatClient($fake, 'test-model');

        $response = $testClient->chat([Message::user('Test')]);

        $this->assertEquals('   ', $response);
    }

    public function testChatWithMultilineResponse(): void
    {
        $multiline = "Line one.\nLine two.\nLine three.";
        $fake = new ClientFake([$this->createChatResponse($multiline)]);
        $testClient = new LlmChatClient($fake, 'test-model');

        $response = $testClient->chat([Message::user('Test')]);

        $this->assertEquals($multiline, $response);
    }

    public function testChatWithNumberResponse(): void
    {
        $fake = new ClientFake([$this->createChatResponse('42')]);
        $testClient = new LlmChatClient($fake, 'test-model');

        $response = $testClient->chat([Message::user('What is the answer?')]);

        $this->assertEquals('42', $response);
    }

    public function testChatWithJsonResponse(): void
    {
        $json = '{"answer": "yes", "confidence": 0.95}';
        $fake = new ClientFake([$this->createChatResponse($json)]);
        $testClient = new LlmChatClient($fake, 'test-model');

        $response = $testClient->chat([Message::user('Test')]);

        $this->assertEquals($json, $response);
    }
}

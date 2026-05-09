<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use App\Chat\QueryRefiner;
use App\Infrastructure\LlmChatClient;
use OpenAI\Testing\ClientFake;
use Tests\Support\TestCase;

class QueryRefinerTest extends TestCase
{
    private QueryRefiner $refiner;

    protected function setUp(): void
    {
        parent::setUp();
        $fake = new ClientFake([$this->createChatResponse('speckled band mystery')]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $this->refiner = new QueryRefiner($chatClient);
    }

    public function testRefinerIsInstanceOfCorrectClass(): void
    {
        $this->assertInstanceOf(QueryRefiner::class, $this->refiner);
    }

    public function testRefineReturnsShortenedQuery(): void
    {
        $question = 'What was the speckled band in The Adventure of the Speckled Band story about?';
        $expectedRefined = 'speckled band mystery';

        $fake = new ClientFake([$this->createChatResponse($expectedRefined)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testRefiner = new QueryRefiner($chatClient);

        $result = $testRefiner->refine($question);

        $this->assertEquals($expectedRefined, $result);
    }

    public function testRefineWithSherlockHolmesReference(): void
    {
        $question = 'Can you tell me about Sherlock Holmes and the case of the blue carbuncle?';
        $expectedRefined = 'blue carbuncle case';

        $fake = new ClientFake([$this->createChatResponse($expectedRefined)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testRefiner = new QueryRefiner($chatClient);

        $result = $testRefiner->refine($question);

        $this->assertEquals($expectedRefined, $result);
    }

    public function testRefineWithSimpleQuestion(): void
    {
        $question = 'Who is Watson?';
        $expectedRefined = 'Watson character';

        $fake = new ClientFake([$this->createChatResponse($expectedRefined)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testRefiner = new QueryRefiner($chatClient);

        $result = $testRefiner->refine($question);

        $this->assertEquals($expectedRefined, $result);
    }

    public function testRefineWithEmptyQuestion(): void
    {
        $expectedRefined = '';

        $fake = new ClientFake([$this->createChatResponse($expectedRefined)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testRefiner = new QueryRefiner($chatClient);

        $result = $testRefiner->refine('');

        $this->assertEquals($expectedRefined, $result);
    }

    public function testRefineReturnsString(): void
    {
        $question = 'What happened?';

        $fake = new ClientFake([$this->createChatResponse('Something happened')]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testRefiner = new QueryRefiner($chatClient);

        $result = $testRefiner->refine($question);

        $this->assertIsString($result);
    }

    public function testRefineWithLongQuestion(): void
    {
        $longQuestion = str_repeat('What can you tell me about ', 20) . '?';
        $expectedRefined = 'summary';

        $fake = new ClientFake([$this->createChatResponse($expectedRefined)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testRefiner = new QueryRefiner($chatClient);

        $result = $testRefiner->refine($longQuestion);

        $this->assertEquals($expectedRefined, $result);
    }

    public function testRefineWithUnicodeQuestion(): void
    {
        $question = '¿Quién es Dr. Roylott?';
        $expectedRefined = 'Dr. Roylott';

        $fake = new ClientFake([$this->createChatResponse($expectedRefined)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testRefiner = new QueryRefiner($chatClient);

        $result = $testRefiner->refine($question);

        $this->assertEquals($expectedRefined, $result);
    }

    public function testRefinerImplementsInterface(): void
    {
        $this->assertInstanceOf(\App\Interfaces\IQueryRefiner::class, $this->refiner);
    }

    public function testRefineWithMultipleCalls(): void
    {
        $fake = new ClientFake([
            $this->createChatResponse('first query'),
            $this->createChatResponse('second query'),
            $this->createChatResponse('third query'),
        ]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testRefiner = new QueryRefiner($chatClient);

        $result1 = $testRefiner->refine('Question one?');
        $result2 = $testRefiner->refine('Question two?');
        $result3 = $testRefiner->refine('Question three?');

        $this->assertEquals('first query', $result1);
        $this->assertEquals('second query', $result2);
        $this->assertEquals('third query', $result3);
    }

    public function testRefineWithQuestionMark(): void
    {
        $question = 'What is the answer?';
        $expectedRefined = 'answer';

        $fake = new ClientFake([$this->createChatResponse($expectedRefined)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testRefiner = new QueryRefiner($chatClient);

        $result = $testRefiner->refine($question);

        $this->assertEquals($expectedRefined, $result);
    }

    public function testRefineWithoutQuestionMark(): void
    {
        $question = 'Tell me about Holmes';
        $expectedRefined = 'Holmes information';

        $fake = new ClientFake([$this->createChatResponse($expectedRefined)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testRefiner = new QueryRefiner($chatClient);

        $result = $testRefiner->refine($question);

        $this->assertEquals($expectedRefined, $result);
    }

    public function testRefineWithNumberInQuestion(): void
    {
        $question = 'How many stories are in the collection?';
        $expectedRefined = 'stories count collection';

        $fake = new ClientFake([$this->createChatResponse($expectedRefined)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testRefiner = new QueryRefiner($chatClient);

        $result = $testRefiner->refine($question);

        $this->assertEquals($expectedRefined, $result);
    }

    public function testRefineWithSpecialCharacters(): void
    {
        $question = 'What does @#\$%^ mean?';
        $expectedRefined = 'special characters meaning';

        $fake = new ClientFake([$this->createChatResponse($expectedRefined)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testRefiner = new QueryRefiner($chatClient);

        $result = $testRefiner->refine($question);

        $this->assertEquals($expectedRefined, $result);
    }

    public function testRefineWithAllCapsQuestion(): void
    {
        $question = 'WHO KILLED THE VICTIM?';
        $expectedRefined = 'who killed victim';

        $fake = new ClientFake([$this->createChatResponse($expectedRefined)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testRefiner = new QueryRefiner($chatClient);

        $result = $testRefiner->refine($question);

        $this->assertEquals($expectedRefined, $result);
    }

    public function testRefineWithWhQuestion(): void
    {
        $question = 'Where did the crime take place?';
        $expectedRefined = 'crime location';

        $fake = new ClientFake([$this->createChatResponse($expectedRefined)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testRefiner = new QueryRefiner($chatClient);

        $result = $testRefiner->refine($question);

        $this->assertEquals($expectedRefined, $result);
    }

    public function testRefineWithHowQuestion(): void
    {
        $question = 'How did Holmes solve the mystery?';
        $expectedRefined = 'Holmes solve mystery method';

        $fake = new ClientFake([$this->createChatResponse($expectedRefined)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testRefiner = new QueryRefiner($chatClient);

        $result = $testRefiner->refine($question);

        $this->assertEquals($expectedRefined, $result);
    }

    public function testRefineWithWhyQuestion(): void
    {
        $question = 'Why was the letter sent anonymously?';
        $expectedRefined = 'anonymous letter reason';

        $fake = new ClientFake([$this->createChatResponse($expectedRefined)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testRefiner = new QueryRefiner($chatClient);

        $result = $testRefiner->refine($question);

        $this->assertEquals($expectedRefined, $result);
    }

    public function testRefineWithWhenQuestion(): void
    {
        $question = 'When did the event occur?';
        $expectedRefined = 'event timing';

        $fake = new ClientFake([$this->createChatResponse($expectedRefined)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testRefiner = new QueryRefiner($chatClient);

        $result = $testRefiner->refine($question);

        $this->assertEquals($expectedRefined, $result);
    }

    public function testRefineWithCanQuestion(): void
    {
        $question = 'Can Holmes predict the future?';
        $expectedRefined = 'Holmes predict future';

        $fake = new ClientFake([$this->createChatResponse($expectedRefined)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testRefiner = new QueryRefiner($chatClient);

        $result = $testRefiner->refine($question);

        $this->assertEquals($expectedRefined, $result);
    }
}

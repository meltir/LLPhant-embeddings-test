<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use App\Chat\AnswerGenerator;
use App\Interfaces\IAnswerGenerator;
use App\Infrastructure\LlmChatClient;
use OpenAI\Testing\ClientFake;
use Tests\Support\TestCase;

class AnswerGeneratorTest extends TestCase
{
    private AnswerGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $fake = new ClientFake([$this->createChatResponse('Holmes would say the game is afoot!')]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $this->generator = new AnswerGenerator($chatClient);
    }

    public function testGenerateReturnsAnswer(): void
    {
        $question = 'Who committed the crime?';
        $context = 'Dr. Roylott was seen near the scene.';

        $answer = $this->generator->generate($question, $context);

        $this->assertIsString($answer);
        $this->assertNotEmpty($answer);
    }

    public function testGenerateWithSpecificResponse(): void
    {
        $expectedAnswer = 'Based on the evidence, Sherlock Holmes deduced that the speckled band'
            . ' was a venomous snake used by Dr. Roylott.';
        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate('What was the speckled band?', 'Context about the case.');

        $this->assertEquals($expectedAnswer, $answer);
    }

    public function testGenerateWithEmptyQuestion(): void
    {
        $context = 'Some context provided.';
        $expectedAnswer = 'An answer to nothing.';

        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate('', $context);

        $this->assertEquals($expectedAnswer, $answer);
    }

    public function testGenerateWithEmptyContext(): void
    {
        $question = 'What happened?';
        $expectedAnswer = 'No context available.';

        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate($question, '');

        $this->assertEquals($expectedAnswer, $answer);
    }

    public function testGenerateWithLongQuestion(): void
    {
        $longQuestion = str_repeat('What is the meaning of ', 50) . '?';
        $context = 'Short context.';
        $expectedAnswer = 'A brief answer.';

        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate($longQuestion, $context);

        $this->assertEquals($expectedAnswer, $answer);
    }

    public function testGenerateWithLongContext(): void
    {
        $question = 'What happened?';
        $longContext = str_repeat('Passage from the story. ', 200);
        $expectedAnswer = 'The answer is clear.';

        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate($question, $longContext);

        $this->assertEquals($expectedAnswer, $answer);
    }

    public function testGenerateWithUnicodeContent(): void
    {
        $question = 'Who is the villain?';
        $context = 'Dr. Grimesby Roylott is the antagonist in The Adventure of the Speckled Band.';
        $expectedAnswer = 'Dr. Roylott is the villain.';

        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate($question, $context);

        $this->assertEquals($expectedAnswer, $answer);
    }

    public function testGenerateWithMultilineContext(): void
    {
        $question = 'What is the answer?';
        $context = "Passage one.\n\nPassage two.\n\nPassage three.";
        $expectedAnswer = 'Multi-line answer.';

        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate($question, $context);

        $this->assertEquals($expectedAnswer, $answer);
    }

    public function testGenerateReturnsPlainText(): void
    {
        $expectedAnswer = 'Simple plain text answer without any formatting.';
        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate('Question?', 'Context.');

        $this->assertEquals($expectedAnswer, $answer);
        $this->assertStringNotContainsString('chatcmpl', $answer);
    }

    public function testGenerateWithSherlockHolmesContext(): void
    {
        $question = 'How did Watson die?';
        $context = 'In the stories, Watson does not die. He survives all adventures.';
        $expectedAnswer = 'Watson does not die in the stories.';

        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate($question, $context);

        $this->assertEquals($expectedAnswer, $answer);
    }

    public function testGenerateWithNumberAnswer(): void
    {
        $expectedAnswer = '42';
        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate('What is the answer?', 'Context.');

        $this->assertEquals('42', $answer);
    }

    public function testGenerateWithJsonAnswer(): void
    {
        $expectedAnswer = '{"deduction": "butler", "confidence": 0.95}';
        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate('Who did it?', 'Clues point to the butler.');

        $this->assertEquals($expectedAnswer, $answer);
    }

    public function testGeneratorImplementsInterface(): void
    {
        $this->assertInstanceOf(IAnswerGenerator::class, $this->generator);
    }

    public function testGenerateWithSpecialCharactersInContext(): void
    {
        $question = 'What does the symbol mean?';
        $context = 'The symbol @#\$%^&*() appeared at the scene.';
        $expectedAnswer = 'The symbol is a red herring.';

        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate($question, $context);

        $this->assertEquals($expectedAnswer, $answer);
    }

    public function testGenerateWithWhitespaceAnswer(): void
    {
        $expectedAnswer = '   ';
        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate('Question?', 'Context.');

        $this->assertEquals('   ', $answer);
    }

    public function testGeneratePreservesAnswerCase(): void
    {
        $expectedAnswer = 'SHERLOCK HOLMES SOLVED THE CASE.';
        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate('Who solved it?', 'Context.');

        $this->assertEquals($expectedAnswer, $answer);
    }
}

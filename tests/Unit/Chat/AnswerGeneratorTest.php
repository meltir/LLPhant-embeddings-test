<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use App\Chat\AnswerGenerator;
use App\Infrastructure\LlmChatClient;
use LLPhant\Chat\Message;
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

    public function test_generator_is_instance_of_correct_class(): void
    {
        $this->assertInstanceOf(AnswerGenerator::class, $this->generator);
    }

    public function test_generate_returns_answer(): void
    {
        $question = 'Who committed the crime?';
        $context = 'Dr. Roylott was seen near the scene.';

        $answer = $this->generator->generate($question, $context);

        $this->assertIsString($answer);
        $this->assertNotEmpty($answer);
    }

    public function test_generate_with_specific_response(): void
    {
        $expectedAnswer = 'Based on the evidence, Sherlock Holmes deduced that the speckled band was a venomous snake used by Dr. Roylott.';
        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate('What was the speckled band?', 'Context about the case.');

        $this->assertEquals($expectedAnswer, $answer);
    }

    public function test_generate_with_empty_question(): void
    {
        $context = 'Some context provided.';
        $expectedAnswer = 'An answer to nothing.';

        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate('', $context);

        $this->assertEquals($expectedAnswer, $answer);
    }

    public function test_generate_with_empty_context(): void
    {
        $question = 'What happened?';
        $expectedAnswer = 'No context available.';

        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate($question, '');

        $this->assertEquals($expectedAnswer, $answer);
    }

    public function test_generate_with_long_question(): void
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

    public function test_generate_with_long_context(): void
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

    public function test_generate_with_unicode_content(): void
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

    public function test_generate_with_multiline_context(): void
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

    public function test_generate_returns_plain_text(): void
    {
        $expectedAnswer = 'Simple plain text answer without any formatting.';
        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate('Question?', 'Context.');

        $this->assertEquals($expectedAnswer, $answer);
        $this->assertStringNotContainsString('chatcmpl', $answer);
    }

    public function test_generate_with_sherlock_holmes_context(): void
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

    public function test_generate_with_number_answer(): void
    {
        $expectedAnswer = '42';
        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate('What is the answer?', 'Context.');

        $this->assertEquals('42', $answer);
    }

    public function test_generate_with_json_answer(): void
    {
        $expectedAnswer = '{"deduction": "butler", "confidence": 0.95}';
        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate('Who did it?', 'Clues point to the butler.');

        $this->assertEquals($expectedAnswer, $answer);
    }

    public function test_generator_implements_interface(): void
    {
        $this->assertInstanceOf(\App\Interfaces\IAnswerGenerator::class, $this->generator);
    }

    public function test_generate_with_special_characters_in_context(): void
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

    public function test_generate_with_whitespace_answer(): void
    {
        $expectedAnswer = '   ';
        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate('Question?', 'Context.');

        $this->assertEquals('   ', $answer);
    }

    public function test_generate_preserves_answer_case(): void
    {
        $expectedAnswer = 'SHERLOCK HOLMES SOLVED THE CASE.';
        $fake = new ClientFake([$this->createChatResponse($expectedAnswer)]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testGenerator = new AnswerGenerator($chatClient);

        $answer = $testGenerator->generate('Who solved it?', 'Context.');

        $this->assertEquals($expectedAnswer, $answer);
    }
}

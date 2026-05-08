<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use App\Chat\ContextAssessor;
use App\Infrastructure\LlmChatClient;
use OpenAI\Testing\ClientFake;
use Tests\Support\TestCase;

class ContextAssessorTest extends TestCase
{
    private ContextAssessor $assessor;

    protected function setUp(): void
    {
        parent::setUp();
        $fake = new ClientFake([$this->createChatResponse('ENOUGH')]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $this->assessor = new ContextAssessor($chatClient);
    }

    public function test_assessor_is_instance_of_correct_class(): void
    {
        $this->assertInstanceOf(ContextAssessor::class, $this->assessor);
    }

    public function test_assess_returns_enough(): void
    {
        $question = 'Who is the murderer?';
        $context = 'Dr. Roylott was found with the murder weapon.';

        $fake = new ClientFake([$this->createChatResponse('ENOUGH')]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testAssessor = new ContextAssessor($chatClient);

        $result = $testAssessor->assess($question, $context);

        $this->assertEquals('ENOUGH', $result);
    }

    public function test_assess_returns_not_enough(): void
    {
        $question = 'Who is the murderer?';
        $context = 'A crime occurred.';

        $fake = new ClientFake([$this->createChatResponse('NOT_ENOUGH')]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testAssessor = new ContextAssessor($chatClient);

        $result = $testAssessor->assess($question, $context);

        $this->assertEquals('NOT_ENOUGH', $result);
    }

    public function test_assess_with_english_response(): void
    {
        $question = 'What happened?';
        $context = 'Relevant information is present in the text.';

        $fake = new ClientFake([$this->createChatResponse('ENOUGH')]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testAssessor = new ContextAssessor($chatClient);

        $result = $testAssessor->assess($question, $context);

        $this->assertContains($result, ['ENOUGH', 'NOT_ENOUGH']);
    }

    public function test_assess_with_empty_question(): void
    {
        $context = 'Some context is provided.';

        $fake = new ClientFake([$this->createChatResponse('ENOUGH')]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testAssessor = new ContextAssessor($chatClient);

        $result = $testAssessor->assess('', $context);

        $this->assertIsString($result);
    }

    public function test_assess_with_empty_context(): void
    {
        $question = 'What is the answer?';

        $fake = new ClientFake([$this->createChatResponse('NOT_ENOUGH')]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testAssessor = new ContextAssessor($chatClient);

        $result = $testAssessor->assess($question, '');

        $this->assertEquals('NOT_ENOUGH', $result);
    }

    public function test_assess_with_long_context(): void
    {
        $question = 'What happened?';
        $longContext = str_repeat('Relevant passage from the story. ', 200);

        $fake = new ClientFake([$this->createChatResponse('ENOUGH')]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testAssessor = new ContextAssessor($chatClient);

        $result = $testAssessor->assess($question, $longContext);

        $this->assertEquals('ENOUGH', $result);
    }

    public function test_assess_with_long_question(): void
    {
        $question = str_repeat('What is the details about ', 50) . '?';
        $context = 'The relevant information is here in this passage.';

        $fake = new ClientFake([$this->createChatResponse('ENOUGH')]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testAssessor = new ContextAssessor($chatClient);

        $result = $testAssessor->assess($question, $context);

        $this->assertEquals('ENOUGH', $result);
    }

    public function test_assess_with_unicode_content(): void
    {
        $question = 'Who is the antagonist?';
        $context = 'Dr. Grimesby Roylott is the villain in The Adventure of the Speckled Band.';

        $fake = new ClientFake([$this->createChatResponse('ENOUGH')]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testAssessor = new ContextAssessor($chatClient);

        $result = $testAssessor->assess($question, $context);

        $this->assertEquals('ENOUGH', $result);
    }

    public function test_assess_returns_uppercase_string(): void
    {
        $fake = new ClientFake([$this->createChatResponse('ENOUGH')]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testAssessor = new ContextAssessor($chatClient);

        $result = $testAssessor->assess('Question?', 'Context.');

        $this->assertTrue(in_array($result, ['ENOUGH', 'NOT_ENOUGH'], true));
    }

    public function test_assessor_implements_interface(): void
    {
        $this->assertInstanceOf(\App\Interfaces\IContextAssessor::class, $this->assessor);
    }

    public function test_assess_with_multiline_context(): void
    {
        $question = 'What is the conclusion?';
        $context = "Passage one from the story.\n\nPassage two with more details.\n\nFinal passage with the answer.";

        $fake = new ClientFake([$this->createChatResponse('ENOUGH')]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testAssessor = new ContextAssessor($chatClient);

        $result = $testAssessor->assess($question, $context);

        $this->assertEquals('ENOUGH', $result);
    }

    public function test_assess_with_sherlock_holmes_question(): void
    {
        $question = 'How did Sherlock Holmes solve the case?';
        $context = 'Holmes used deductive reasoning and observed small details that others missed.';

        $fake = new ClientFake([$this->createChatResponse('ENOUGH')]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testAssessor = new ContextAssessor($chatClient);

        $result = $testAssessor->assess($question, $context);

        $this->assertEquals('ENOUGH', $result);
    }

    public function test_assess_with_multiple_questions(): void
    {
        $fake = new ClientFake([
            $this->createChatResponse('ENOUGH'),
            $this->createChatResponse('NOT_ENOUGH'),
            $this->createChatResponse('ENOUGH'),
        ]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testAssessor = new ContextAssessor($chatClient);

        $result1 = $testAssessor->assess('Question 1?', 'Context 1.');
        $result2 = $testAssessor->assess('Question 2?', 'Context 2.');
        $result3 = $testAssessor->assess('Question 3?', 'Context 3.');

        $this->assertEquals('ENOUGH', $result1);
        $this->assertEquals('NOT_ENOUGH', $result2);
        $this->assertEquals('ENOUGH', $result3);
    }

    public function test_assess_with_special_characters(): void
    {
        $question = 'What does @#\$%^&* mean?';
        $context = 'The symbols are clues left at the scene.';

        $fake = new ClientFake([$this->createChatResponse('NOT_ENOUGH')]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testAssessor = new ContextAssessor($chatClient);

        $result = $testAssessor->assess($question, $context);

        $this->assertEquals('NOT_ENOUGH', $result);
    }

    public function test_assess_with_number_context(): void
    {
        $question = 'What number is mentioned?';
        $context = 'The number 42 appears three times in the passage.';

        $fake = new ClientFake([$this->createChatResponse('ENOUGH')]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testAssessor = new ContextAssessor($chatClient);

        $result = $testAssessor->assess($question, $context);

        $this->assertEquals('ENOUGH', $result);
    }

    public function test_assess_with_json_context(): void
    {
        $question = 'What is in the JSON?';
        $context = '{"clue": "mud", "location": "garden", "time": "midnight"}';

        $fake = new ClientFake([$this->createChatResponse('ENOUGH')]);
        $chatClient = new LlmChatClient($fake, 'test-model');
        $testAssessor = new ContextAssessor($chatClient);

        $result = $testAssessor->assess($question, $context);

        $this->assertEquals('ENOUGH', $result);
    }
}

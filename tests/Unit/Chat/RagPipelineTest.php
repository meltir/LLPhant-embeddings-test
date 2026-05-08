<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use App\Chat\AnswerGenerator;
use App\Chat\ContextAssessor;
use App\Chat\QueryRefiner;
use App\Chat\RagPipeline;
use App\Interfaces\IVectorSearch;
use LLPhant\Embeddings\Document;
use OpenAI\Testing\ClientFake;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Support\TestCase;

class RagPipelineTest extends TestCase
{
    private MockObject&IVectorSearch $vectorSearch;
    private MockObject&QueryRefiner $queryRefiner;
    private MockObject&ContextAssessor $contextAssessor;
    private MockObject&AnswerGenerator $answerGenerator;
    private RagPipeline $pipeline;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vectorSearch = $this->createMock(IVectorSearch::class);
        $this->queryRefiner = $this->createMock(QueryRefiner::class);
        $this->contextAssessor = $this->createMock(ContextAssessor::class);
        $this->answerGenerator = $this->createMock(AnswerGenerator::class);

        $this->pipeline = new RagPipeline(
            $this->vectorSearch,
            $this->queryRefiner,
            $this->contextAssessor,
            $this->answerGenerator
        );
    }

    public function test_pipeline_is_instance_of_correct_class(): void
    {
        $this->assertInstanceOf(RagPipeline::class, $this->pipeline);
    }

    public function test_ask_returns_answer_with_enough_context(): void
    {
        $question = 'Who is the murderer?';
        $refinedQuery = 'murderer identity';
        $context = 'Dr. Roylott was found with the weapon.';
        $answer = 'Dr. Roylott is the murderer.';
        $embedding = $this->makeEmbeddingVector(768);

        $this->queryRefiner->method('refine')->willReturn($refinedQuery);
        $this->vectorSearch->method('search')->willReturn([
            $this->createDocument($context, 'The Adventure', 1),
        ]);
        $this->contextAssessor->method('assess')->willReturn('ENOUGH');
        $this->answerGenerator->method('generate')->willReturn($answer);

        $result = $this->pipeline->ask($question);

        $this->assertEquals($answer, $result['answer']);
        $this->assertEquals($refinedQuery, $result['refinedQuery']);
        $this->assertFalse($result['expanded']);
        $this->assertStringContainsString('The Adventure', $result['context']);
    }

    public function test_ask_expands_search_when_context_insufficient(): void
    {
        $question = 'What happened?';
        $refinedQuery = 'what happened';
        $context1 = 'Initial context is insufficient.';
        $context2 = 'Additional context provides more details.';
        $answer = 'The full story is now clear.';
        $embedding = $this->makeEmbeddingVector(768);

        $this->queryRefiner->method('refine')->willReturn($refinedQuery);
        $this->vectorSearch->method('search')
            ->willReturnOnConsecutiveCalls(
                [$this->createDocument($context1, 'Story', 1)],
                [$this->createDocument($context1, 'Story', 1), $this->createDocument($context2, 'Story', 2)]
            );
        $this->contextAssessor->method('assess')
            ->willReturnOnConsecutiveCalls('NOT_ENOUGH', 'ENOUGH');
        $this->answerGenerator->method('generate')->willReturn($answer);

        $result = $this->pipeline->ask($question);

        $this->assertEquals($answer, $result['answer']);
        $this->assertTrue($result['expanded']);
        $this->assertStringContainsString('Initial context', $result['context']);
        $this->assertStringContainsString('Additional context', $result['context']);
    }

    public function test_ask_returns_correct_structure(): void
    {
        $embedding = $this->makeEmbeddingVector(768);

        $this->queryRefiner->method('refine')->willReturn('refined');
        $this->vectorSearch->method('search')->willReturn([]);
        $this->contextAssessor->method('assess')->willReturn('ENOUGH');
        $this->answerGenerator->method('generate')->willReturn('Answer');

        $result = $this->pipeline->ask('Question?');

        $this->assertArrayHasKey('answer', $result);
        $this->assertArrayHasKey('refinedQuery', $result);
        $this->assertArrayHasKey('expanded', $result);
        $this->assertArrayHasKey('context', $result);
        $this->assertIsArray($result);
    }

    public function test_ask_with_empty_context_still_returns_answer(): void
    {
        $embedding = $this->makeEmbeddingVector(768);

        $this->queryRefiner->method('refine')->willReturn('');
        $this->vectorSearch->method('search')->willReturn([]);
        $this->contextAssessor->method('assess')->willReturn('ENOUGH');
        $this->answerGenerator->method('generate')->willReturn('No context answer');

        $result = $this->pipeline->ask('Question?');

        $this->assertEquals('No context answer', $result['answer']);
        $this->assertFalse($result['expanded']);
    }

    public function test_ask_calls_refine_once(): void
    {
        $this->queryRefiner->expects($this->once())
            ->method('refine')
            ->with('Who is Holmes?')
            ->willReturn('Holmes detective');

        $this->vectorSearch->method('search')->willReturn([]);
        $this->contextAssessor->method('assess')->willReturn('ENOUGH');
        $this->answerGenerator->method('generate')->willReturn('Answer');

        $this->pipeline->ask('Who is Holmes?');
    }

    public function test_ask_calls_vector_search_twice_when_expanding(): void
    {
        $this->queryRefiner->method('refine')->willReturn('query');

        $this->vectorSearch->expects($this->exactly(2))
            ->method('search');

        $this->contextAssessor->method('assess')
            ->willReturnOnConsecutiveCalls('NOT_ENOUGH', 'ENOUGH');
        $this->answerGenerator->method('generate')->willReturn('Answer');

        $this->pipeline->ask('Question?');
    }

    public function test_ask_calls_vector_search_once_when_not_expanding(): void
    {
        $this->queryRefiner->method('refine')->willReturn('query');

        $this->vectorSearch->expects($this->once())
            ->method('search');

        $this->contextAssessor->method('assess')->willReturn('ENOUGH');
        $this->answerGenerator->method('generate')->willReturn('Answer');

        $this->pipeline->ask('Question?');
    }

    public function test_ask_with_unicode_question(): void
    {
        $embedding = $this->makeEmbeddingVector(768);

        $this->queryRefiner->method('refine')->willReturn('refined');
        $this->vectorSearch->method('search')->willReturn([
            $this->createDocument('Contexto en español.', 'Spanish Story', 1),
        ]);
        $this->contextAssessor->method('assess')->willReturn('ENOUGH');
        $this->answerGenerator->method('generate')->willReturn('Respuesta en español.');

        $result = $this->pipeline->ask('¿Quién es Holmes?');

        $this->assertEquals('Respuesta en español.', $result['answer']);
    }

    public function test_ask_with_multiple_documents(): void
    {
        $embedding = $this->makeEmbeddingVector(768);
        $docs = [
            $this->createDocument('Document one content.', 'Story 1', 1),
            $this->createDocument('Document two content.', 'Story 2', 2),
            $this->createDocument('Document three content.', 'Story 3', 3),
        ];

        $this->queryRefiner->method('refine')->willReturn('query');
        $this->vectorSearch->method('search')->willReturn($docs);
        $this->contextAssessor->method('assess')->willReturn('ENOUGH');
        $this->answerGenerator->method('generate')->willReturn('Answer');

        $result = $this->pipeline->ask('Question?');

        $this->assertStringContainsString('Story 1', $result['context']);
        $this->assertStringContainsString('Story 2', $result['context']);
        $this->assertStringContainsString('Story 3', $result['context']);
    }

    public function test_ask_with_long_answer(): void
    {
        $embedding = $this->makeEmbeddingVector(768);
        $longAnswer = str_repeat('This is a detailed answer. ', 50);

        $this->queryRefiner->method('refine')->willReturn('query');
        $this->vectorSearch->method('search')->willReturn([]);
        $this->contextAssessor->method('assess')->willReturn('ENOUGH');
        $this->answerGenerator->method('generate')->willReturn($longAnswer);

        $result = $this->pipeline->ask('Question?');

        $this->assertEquals($longAnswer, $result['answer']);
    }

    public function test_ask_with_special_characters_in_question(): void
    {
        $embedding = $this->makeEmbeddingVector(768);

        $this->queryRefiner->method('refine')->willReturn('query');
        $this->vectorSearch->method('search')->willReturn([]);
        $this->contextAssessor->method('assess')->willReturn('ENOUGH');
        $this->answerGenerator->method('generate')->willReturn('Answer');

        $result = $this->pipeline->ask('What does @#\$%^& mean?');

        $this->assertEquals('Answer', $result['answer']);
    }

    public function test_ask_with_number_question(): void
    {
        $embedding = $this->makeEmbeddingVector(768);

        $this->queryRefiner->method('refine')->willReturn('query');
        $this->vectorSearch->method('search')->willReturn([]);
        $this->contextAssessor->method('assess')->willReturn('ENOUGH');
        $this->answerGenerator->method('generate')->willReturn('42');

        $result = $this->pipeline->ask('What is the answer?');

        $this->assertEquals('42', $result['answer']);
    }

    public function test_ask_with_json_context(): void
    {
        $embedding = $this->makeEmbeddingVector(768);

        $this->queryRefiner->method('refine')->willReturn('query');
        $this->vectorSearch->method('search')->willReturn([
            $this->createDocument('{"clue": "mud", "location": "garden"}', 'JSON Story', 1),
        ]);
        $this->contextAssessor->method('assess')->willReturn('ENOUGH');
        $this->answerGenerator->method('generate')->willReturn('Answer');

        $result = $this->pipeline->ask('Question?');

        $this->assertStringContainsString('clue', $result['context']);
    }

    public function test_ask_with_multiline_context(): void
    {
        $embedding = $this->makeEmbeddingVector(768);
        $multilineContext = "Line one.\n\nLine two.\n\nLine three.";

        $this->queryRefiner->method('refine')->willReturn('query');
        $this->vectorSearch->method('search')->willReturn([
            $this->createDocument($multilineContext, 'Multi Story', 1),
        ]);
        $this->contextAssessor->method('assess')->willReturn('ENOUGH');
        $this->answerGenerator->method('generate')->willReturn('Answer');

        $result = $this->pipeline->ask('Question?');

        $this->assertStringContainsString('Line one', $result['context']);
    }

    public function test_ask_with_default_k_values(): void
    {
        $embedding = $this->makeEmbeddingVector(768);

        $this->queryRefiner->method('refine')->willReturn('query');

        $callCount = 0;
        $this->vectorSearch->expects($this->exactly(2))
            ->method('search')
            ->willReturnCallback(function ($embedding, $k) use (&$callCount) {
                $callCount++;
                if ($callCount === 1) {
                    $this->assertEquals(4, $k);
                } else {
                    $this->assertEquals(12, $k);
                }
                return [];
            });

        $this->contextAssessor->method('assess')
            ->willReturnOnConsecutiveCalls('NOT_ENOUGH', 'ENOUGH');
        $this->answerGenerator->method('generate')->willReturn('Answer');

        $this->pipeline->ask('Question?');
    }

    public function test_ask_with_custom_k_values(): void
    {
        $embedding = $this->makeEmbeddingVector(768);

        $this->queryRefiner->method('refine')->willReturn('query');
        $this->vectorSearch->method('search')->willReturn([]);
        $this->contextAssessor->method('assess')->willReturn('ENOUGH');
        $this->answerGenerator->method('generate')->willReturn('Answer');

        $this->vectorSearch->expects($this->once())
            ->method('search')
            ->with($this->anything(), 8);

        $this->pipeline->ask('Question?', 8);
    }

    public function test_ask_returns_refined_query(): void
    {
        $expectedRefined = 'refined search query';

        $this->queryRefiner->method('refine')->willReturn($expectedRefined);
        $this->vectorSearch->method('search')->willReturn([]);
        $this->contextAssessor->method('assess')->willReturn('ENOUGH');
        $this->answerGenerator->method('generate')->willReturn('Answer');

        $result = $this->pipeline->ask('Original question?');

        $this->assertEquals($expectedRefined, $result['refinedQuery']);
    }

    public function test_ask_context_contains_chunk_numbers(): void
    {
        $embedding = $this->makeEmbeddingVector(768);

        $this->queryRefiner->method('refine')->willReturn('query');
        $this->vectorSearch->method('search')->willReturn([
            $this->createDocument('Content 1', 'Story', 1),
            $this->createDocument('Content 2', 'Story', 2),
        ]);
        $this->contextAssessor->method('assess')->willReturn('ENOUGH');
        $this->answerGenerator->method('generate')->willReturn('Answer');

        $result = $this->pipeline->ask('Question?');

        $this->assertStringContainsString('Chunk: 1', $result['context']);
        $this->assertStringContainsString('Chunk: 2', $result['context']);
    }

    public function test_ask_with_whitespace_answer(): void
    {
        $embedding = $this->makeEmbeddingVector(768);

        $this->queryRefiner->method('refine')->willReturn('query');
        $this->vectorSearch->method('search')->willReturn([]);
        $this->contextAssessor->method('assess')->willReturn('ENOUGH');
        $this->answerGenerator->method('generate')->willReturn('   ');

        $result = $this->pipeline->ask('Question?');

        $this->assertEquals('   ', $result['answer']);
    }

    public function test_ask_with_expanded_context_from_different_stories(): void
    {
        $embedding = $this->makeEmbeddingVector(768);

        $this->queryRefiner->method('refine')->willReturn('query');
        $this->vectorSearch->method('search')
            ->willReturnOnConsecutiveCalls(
                [$this->createDocument('Context from Story A.', 'Story A', 1)],
                [
                    $this->createDocument('Context from Story A.', 'Story A', 1),
                    $this->createDocument('Context from Story B.', 'Story B', 1),
                ]
            );
        $this->contextAssessor->method('assess')
            ->willReturnOnConsecutiveCalls('NOT_ENOUGH', 'ENOUGH');
        $this->answerGenerator->method('generate')->willReturn('Combined answer');

        $result = $this->pipeline->ask('Question?');

        $this->assertTrue($result['expanded']);
        $this->assertStringContainsString('Story A', $result['context']);
        $this->assertStringContainsString('Story B', $result['context']);
    }

    public function test_ask_with_punctuation_in_question(): void
    {
        $embedding = $this->makeEmbeddingVector(768);

        $this->queryRefiner->method('refine')->willReturn('query');
        $this->vectorSearch->method('search')->willReturn([]);
        $this->contextAssessor->method('assess')->willReturn('ENOUGH');
        $this->answerGenerator->method('generate')->willReturn('Answer');

        $result = $this->pipeline->ask('What?! Really???');

        $this->assertEquals('Answer', $result['answer']);
    }
}

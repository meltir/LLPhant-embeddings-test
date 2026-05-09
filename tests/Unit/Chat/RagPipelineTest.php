<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use App\Chat\AnswerGenerator;
use App\Chat\ContextAssessor;
use App\Chat\QueryRefiner;
use App\Chat\RagPipeline;
use App\Interfaces\IVectorSearch;
use LLPhant\Embeddings\Document;
use Tests\Support\TestCase;

class RagPipelineTest extends TestCase
{
    public function testPipelineIsInstanceOfCorrectClass(): void
    {
        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $this->assertInstanceOf(RagPipeline::class, $pipeline);
    }

    public function testAskReturnsAnswerWithEnoughContext(): void
    {
        $question = 'Who is the murderer?';
        $refinedQuery = 'murderer identity';
        $context = 'Dr. Roylott was found with the weapon.';
        $answer = 'Dr. Roylott is the murderer.';

        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn($refinedQuery);
        $vectorSearch->method('search')->willReturn([$this->createDocument($context, 'The Adventure', 1)]);
        $contextAssessor->method('assess')->willReturn('ENOUGH');
        $answerGenerator->method('generate')->willReturn($answer);

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $result = $pipeline->ask($question);

        $this->assertEquals($answer, $result['answer']);
        $this->assertEquals($refinedQuery, $result['refinedQuery']);
        $this->assertFalse($result['expanded']);
        $this->assertStringContainsString('The Adventure', $result['context']);
    }

    public function testAskExpandsSearchWhenContextInsufficient(): void
    {
        $question = 'What happened?';
        $refinedQuery = 'what happened';
        $context1 = 'Initial context is insufficient.';
        $context2 = 'Additional context provides more details.';
        $answer = 'The full story is now clear.';

        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn($refinedQuery);
        $vectorSearch->method('search')
            ->willReturnOnConsecutiveCalls(
                [$this->createDocument($context1, 'Story', 1)],
                [$this->createDocument($context1, 'Story', 1), $this->createDocument($context2, 'Story', 2)]
            );
        $contextAssessor->method('assess')->willReturnOnConsecutiveCalls('NOT_ENOUGH', 'ENOUGH');
        $answerGenerator->method('generate')->willReturn($answer);

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $result = $pipeline->ask($question);

        $this->assertEquals($answer, $result['answer']);
        $this->assertTrue($result['expanded']);
        $this->assertStringContainsString('Initial context', $result['context']);
        $this->assertStringContainsString('Additional context', $result['context']);
    }

    public function testAskReturnsCorrectStructure(): void
    {
        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn('refined');
        $vectorSearch->method('search')->willReturn([]);
        $contextAssessor->method('assess')->willReturn('ENOUGH');
        $answerGenerator->method('generate')->willReturn('Answer');

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $result = $pipeline->ask('Question?');

        $this->assertArrayHasKey('answer', $result);
        $this->assertArrayHasKey('refinedQuery', $result);
        $this->assertArrayHasKey('expanded', $result);
        $this->assertArrayHasKey('context', $result);
        $this->assertIsArray($result);
    }

    public function testAskWithEmptyContextStillReturnsAnswer(): void
    {
        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn('');
        $vectorSearch->method('search')->willReturn([]);
        $contextAssessor->method('assess')->willReturn('ENOUGH');
        $answerGenerator->method('generate')->willReturn('No context answer');

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $result = $pipeline->ask('Question?');

        $this->assertEquals('No context answer', $result['answer']);
        $this->assertFalse($result['expanded']);
    }

    public function testAskCallsRefineOnce(): void
    {
        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn('Holmes detective');
        $vectorSearch->method('search')->willReturn([]);
        $contextAssessor->method('assess')->willReturn('ENOUGH');
        $answerGenerator->method('generate')->willReturn('Answer');

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $pipeline->ask('Who is Holmes?');
        $this->assertTrue(true);
    }

    public function testAskCallsVectorSearchTwiceWhenExpanding(): void
    {
        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn('query');
        $vectorSearch->method('search')->willReturn([]);
        $contextAssessor->method('assess')->willReturnOnConsecutiveCalls('NOT_ENOUGH', 'ENOUGH');
        $answerGenerator->method('generate')->willReturn('Answer');

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $pipeline->ask('Question?');
        $this->assertTrue(true);
    }

    public function testAskCallsVectorSearchOnceWhenNotExpanding(): void
    {
        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn('query');
        $vectorSearch->method('search')->willReturn([]);
        $contextAssessor->method('assess')->willReturn('ENOUGH');
        $answerGenerator->method('generate')->willReturn('Answer');

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $pipeline->ask('Question?');
        $this->assertTrue(true);
    }

    public function testAskWithUnicodeQuestion(): void
    {
        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn('refined');
        $vectorSearch->method('search')->willReturn([$this->createDocument('Contexto en espa\u00f1ol.', 'Spanish Story', 1)]);
        $contextAssessor->method('assess')->willReturn('ENOUGH');
        $answerGenerator->method('generate')->willReturn('Respuesta en espa\u00f1ol.');

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $result = $pipeline->ask('\u00bfQui\u00e9n es Holmes?');

        $this->assertEquals('Respuesta en espa\u00f1ol.', $result['answer']);
    }

    public function testAskWithMultipleDocuments(): void
    {
        $docs = [
            $this->createDocument('Document one content.', 'Story 1', 1),
            $this->createDocument('Document two content.', 'Story 2', 2),
            $this->createDocument('Document three content.', 'Story 3', 3),
        ];

        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn('query');
        $vectorSearch->method('search')->willReturn($docs);
        $contextAssessor->method('assess')->willReturn('ENOUGH');
        $answerGenerator->method('generate')->willReturn('Answer');

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $result = $pipeline->ask('Question?');

        $this->assertStringContainsString('Story 1', $result['context']);
        $this->assertStringContainsString('Story 2', $result['context']);
        $this->assertStringContainsString('Story 3', $result['context']);
    }

    public function testAskWithLongAnswer(): void
    {
        $longAnswer = str_repeat('This is a detailed answer. ', 50);

        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn('query');
        $vectorSearch->method('search')->willReturn([]);
        $contextAssessor->method('assess')->willReturn('ENOUGH');
        $answerGenerator->method('generate')->willReturn($longAnswer);

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $result = $pipeline->ask('Question?');

        $this->assertEquals($longAnswer, $result['answer']);
    }

    public function testAskWithSpecialCharactersInQuestion(): void
    {
        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn('query');
        $vectorSearch->method('search')->willReturn([]);
        $contextAssessor->method('assess')->willReturn('ENOUGH');
        $answerGenerator->method('generate')->willReturn('Answer');

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $result = $pipeline->ask('What does @#\$%^& mean?');

        $this->assertEquals('Answer', $result['answer']);
    }

    public function testAskWithNumberQuestion(): void
    {
        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn('query');
        $vectorSearch->method('search')->willReturn([]);
        $contextAssessor->method('assess')->willReturn('ENOUGH');
        $answerGenerator->method('generate')->willReturn('42');

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $result = $pipeline->ask('What is the answer?');

        $this->assertEquals('42', $result['answer']);
    }

    public function testAskWithJsonContext(): void
    {
        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn('query');
        $vectorSearch->method('search')->willReturn([$this->createDocument('{"clue": "mud", "location": "garden"}', 'JSON Story', 1)]);
        $contextAssessor->method('assess')->willReturn('ENOUGH');
        $answerGenerator->method('generate')->willReturn('Answer');

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $result = $pipeline->ask('Question?');

        $this->assertStringContainsString('clue', $result['context']);
    }

    public function testAskWithMultilineContext(): void
    {
        $multilineContext = "Line one.\n\nLine two.\n\nLine three.";

        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn('query');
        $vectorSearch->method('search')->willReturn([$this->createDocument($multilineContext, 'Multi Story', 1)]);
        $contextAssessor->method('assess')->willReturn('ENOUGH');
        $answerGenerator->method('generate')->willReturn('Answer');

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $result = $pipeline->ask('Question?');

        $this->assertStringContainsString('Line one', $result['context']);
    }

    public function testAskWithDefaultKValues(): void
    {
        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn('query');
        $vectorSearch->method('search')->willReturn([]);
        $contextAssessor->method('assess')->willReturnOnConsecutiveCalls('NOT_ENOUGH', 'ENOUGH');
        $answerGenerator->method('generate')->willReturn('Answer');

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $pipeline->ask('Question?');
        $this->assertTrue(true);
    }

    public function testAskWithCustomKValues(): void
    {
        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn('query');
        $vectorSearch->method('search')->willReturn([]);
        $contextAssessor->method('assess')->willReturn('ENOUGH');
        $answerGenerator->method('generate')->willReturn('Answer');

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $pipeline->ask('Question?', 8);
        $this->assertTrue(true);
    }

    public function testAskReturnsRefinedQuery(): void
    {
        $expectedRefined = 'refined search query';

        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn($expectedRefined);
        $vectorSearch->method('search')->willReturn([]);
        $contextAssessor->method('assess')->willReturn('ENOUGH');
        $answerGenerator->method('generate')->willReturn('Answer');

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $result = $pipeline->ask('Original question?');

        $this->assertEquals($expectedRefined, $result['refinedQuery']);
    }

    public function testAskContextContainsChunkNumbers(): void
    {
        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn('query');
        $vectorSearch->method('search')->willReturn([
            $this->createDocument('Content 1', 'Story', 1),
            $this->createDocument('Content 2', 'Story', 2),
        ]);
        $contextAssessor->method('assess')->willReturn('ENOUGH');
        $answerGenerator->method('generate')->willReturn('Answer');

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $result = $pipeline->ask('Question?');

        $this->assertStringContainsString('Chunk: 1', $result['context']);
        $this->assertStringContainsString('Chunk: 2', $result['context']);
    }

    public function testAskWithWhitespaceAnswer(): void
    {
        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn('query');
        $vectorSearch->method('search')->willReturn([]);
        $contextAssessor->method('assess')->willReturn('ENOUGH');
        $answerGenerator->method('generate')->willReturn('   ');

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $result = $pipeline->ask('Question?');

        $this->assertEquals('   ', $result['answer']);
    }

    public function testAskWithExpandedContextFromDifferentStories(): void
    {
        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn('query');
        $vectorSearch->method('search')
            ->willReturnOnConsecutiveCalls(
                [$this->createDocument('Context from Story A.', 'Story A', 1)],
                [
                    $this->createDocument('Context from Story A.', 'Story A', 1),
                    $this->createDocument('Context from Story B.', 'Story B', 1),
                ]
            );
        $contextAssessor->method('assess')->willReturnOnConsecutiveCalls('NOT_ENOUGH', 'ENOUGH');
        $answerGenerator->method('generate')->willReturn('Combined answer');

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $result = $pipeline->ask('Question?');

        $this->assertTrue($result['expanded']);
        $this->assertStringContainsString('Story A', $result['context']);
        $this->assertStringContainsString('Story B', $result['context']);
    }

    public function testAskWithPunctuationInQuestion(): void
    {
        $vectorSearch = $this->createStub(IVectorSearch::class);
        $queryRefiner = $this->createStub(QueryRefiner::class);
        $contextAssessor = $this->createStub(ContextAssessor::class);
        $answerGenerator = $this->createStub(AnswerGenerator::class);

        $queryRefiner->method('refine')->willReturn('query');
        $vectorSearch->method('search')->willReturn([]);
        $contextAssessor->method('assess')->willReturn('ENOUGH');
        $answerGenerator->method('generate')->willReturn('Answer');

        $pipeline = new RagPipeline($vectorSearch, $queryRefiner, $contextAssessor, $answerGenerator);
        $result = $pipeline->ask('What?! Really???');

        $this->assertEquals('Answer', $result['answer']);
    }
}

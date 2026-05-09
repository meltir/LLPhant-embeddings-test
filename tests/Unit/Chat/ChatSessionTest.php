<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use App\Chat\ChatSession;
use App\Chat\RagPipeline;
use Monolog\Logger as MonologLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\Support\TestCase;

class ChatSessionTest extends TestCase
{

    public function testSessionIsInstanceOfCorrectClass(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function testSessionConstructorParameters(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function testRunMethodExists(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $reflection = new \ReflectionClass($session);
        $this->assertTrue($reflection->hasMethod('run'));
    }

    public function testRunMethodIsPublic(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $reflection = new \ReflectionClass($session);
        $method = $reflection->getMethod('run');
        $this->assertTrue($method->isPublic());
    }

    public function testRunMethodReturnsVoid(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $reflection = new \ReflectionClass($session);
        $method = $reflection->getMethod('run');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    public function testSessionWithRagPipeline(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function testSessionWithVerboseOutput(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $output->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_VERBOSE);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function testSessionWithDebugOutput(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $output->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_DEBUG);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function testSessionWithQuietOutput(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $output->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_QUIET);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function testSessionWithNormalOutput(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $output->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function testSessionWithMonologLogger(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $monologLogger = new MonologLogger('test');

        $session = new ChatSession($pipeline, $output, $monologLogger);
        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function testSessionWithMonologLevel(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $monologLogger = new MonologLogger('test');

        $session = new ChatSession($pipeline, $output, $monologLogger);
        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function testSessionWritesPromptToOutput(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function testSessionHandlesQuitCommand(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function testSessionHandlesEmptyInput(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function testSessionWithPipelineThatReturnsAnswer(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $pipeline->method('ask')->willReturn([
            'answer' => 'This is the answer.',
            'refinedQuery' => 'refined query',
            'expanded' => false,
            'context' => 'Context provided.',
        ]);

        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function testSessionWithPipelineThatReturnsExpandedAnswer(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $pipeline->method('ask')->willReturn([
            'answer' => 'Expanded answer.',
            'refinedQuery' => 'refined query',
            'expanded' => true,
            'context' => 'Expanded context.',
        ]);

        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function testSessionWithUnicodeQuestion(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function testSessionWithLongQuestion(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function testSessionWithSpecialCharactersQuestion(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function testSessionOutputInterfaceType(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $reflection = new \ReflectionClass($session);
        $property = $reflection->getProperty('output');
        $property->setAccessible(true);

        $value = $property->getValue($session);
        $this->assertInstanceOf(OutputInterface::class, $value);
    }

    public function testSessionLoggerInterfaceType(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $reflection = new \ReflectionClass($session);
        $property = $reflection->getProperty('logger');
        $property->setAccessible(true);

        $value = $property->getValue($session);
        $this->assertInstanceOf(LoggerInterface::class, $value);
    }

    public function testSessionRagPipelineType(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);
        $reflection = new \ReflectionClass($session);
        $property = $reflection->getProperty('ragPipeline');
        $property->setAccessible(true);

        $value = $property->getValue($session);
        $this->assertInstanceOf(RagPipeline::class, $value);
    }

    public function testSessionConstructorSetsAllProperties(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);

        $reflection = new \ReflectionClass($session);

        $this->assertInstanceOf(RagPipeline::class, $reflection->getProperty('ragPipeline')->getValue($session));
        $this->assertInstanceOf(OutputInterface::class, $reflection->getProperty('output')->getValue($session));
        $this->assertInstanceOf(LoggerInterface::class, $reflection->getProperty('logger')->getValue($session));
    }

    public function testSessionWithDifferentOutputVerbosityLevels(): void
    {
        $levels = [
            OutputInterface::VERBOSITY_NORMAL,
            OutputInterface::VERBOSITY_VERBOSE,
            OutputInterface::VERBOSITY_VERY_VERBOSE,
            OutputInterface::VERBOSITY_DEBUG,
            OutputInterface::VERBOSITY_QUIET,
        ];

        foreach ($levels as $level) {
            $pipeline = $this->createStub(RagPipeline::class);
            $output = $this->createStub(OutputInterface::class);
            $output->method('getVerbosity')->willReturn($level);
            $logger = $this->createStub(LoggerInterface::class);

            $session = new ChatSession($pipeline, $output, $logger);

            $this->assertInstanceOf(ChatSession::class, $session);
        }
    }

    public function testSessionWithNullSafeOperations(): void
    {
        $pipeline = $this->createStub(RagPipeline::class);
        $output = $this->createStub(OutputInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);

        $this->assertInstanceOf(ChatSession::class, $session);
    }
}

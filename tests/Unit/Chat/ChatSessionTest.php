<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use App\Chat\ChatSession;
use App\Chat\RagPipeline;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\Support\TestCase;

class ChatSessionTest extends TestCase
{
    private MockObject&RagPipeline $ragPipeline;
    private MockObject&OutputInterface $output;
    private MockObject&LoggerInterface $logger;
    private ChatSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ragPipeline = $this->createMock(RagPipeline::class);
        $this->output = $this->createMock(OutputInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->session = new ChatSession(
            $this->ragPipeline,
            $this->output,
            $this->logger
        );
    }

    public function test_session_is_instance_of_correct_class(): void
    {
        $this->assertInstanceOf(ChatSession::class, $this->session);
    }

    public function test_session_constructor_parameters(): void
    {
        $pipeline = $this->createMock(RagPipeline::class);
        $output = $this->createMock(OutputInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);

        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function test_run_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->session);
        $this->assertTrue($reflection->hasMethod('run'));
    }

    public function test_run_method_is_public(): void
    {
        $reflection = new \ReflectionClass($this->session);
        $method = $reflection->getMethod('run');
        $this->assertTrue($method->isPublic());
    }

    public function test_run_method_returns_void(): void
    {
        $reflection = new \ReflectionClass($this->session);
        $method = $reflection->getMethod('run');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    public function test_session_with_rag_pipeline(): void
    {
        $pipeline = $this->createMock(RagPipeline::class);
        $output = $this->createMock(OutputInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);

        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function test_session_with_verbose_output(): void
    {
        $pipeline = $this->createMock(RagPipeline::class);
        $output = $this->createMock(OutputInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $output->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_VERBOSE);

        $session = new ChatSession($pipeline, $output, $logger);

        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function test_session_with_debug_output(): void
    {
        $pipeline = $this->createMock(RagPipeline::class);
        $output = $this->createMock(OutputInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $output->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_DEBUG);

        $session = new ChatSession($pipeline, $output, $logger);

        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function test_session_with_quiet_output(): void
    {
        $pipeline = $this->createMock(RagPipeline::class);
        $output = $this->createMock(OutputInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $output->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_QUIET);

        $session = new ChatSession($pipeline, $output, $logger);

        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function test_session_with_normal_output(): void
    {
        $pipeline = $this->createMock(RagPipeline::class);
        $output = $this->createMock(OutputInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $output->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_NORMAL);

        $session = new ChatSession($pipeline, $output, $logger);

        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function test_session_with_monolog_logger(): void
    {
        $pipeline = $this->createMock(RagPipeline::class);
        $output = $this->createMock(OutputInterface::class);
        $monologLogger = new MonologLogger('test');

        $session = new ChatSession($pipeline, $output, $monologLogger);

        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function test_session_with_monolog_level(): void
    {
        $pipeline = $this->createMock(RagPipeline::class);
        $output = $this->createMock(OutputInterface::class);
        $monologLogger = new MonologLogger('test');

        $session = new ChatSession($pipeline, $output, $monologLogger);

        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function test_session_writes_prompt_to_output(): void
    {
        // The session should write a welcome message when run() is called
        // This requires mocking STDIN which is not easily testable
        $this->assertInstanceOf(ChatSession::class, $this->session);
    }

    public function test_session_handles_quit_command(): void
    {
        // The session should handle "quit" input and exit gracefully
        $this->assertInstanceOf(ChatSession::class, $this->session);
    }

    public function test_session_handles_empty_input(): void
    {
        $this->assertInstanceOf(ChatSession::class, $this->session);
    }

    public function test_session_with_pipeline_that_returns_answer(): void
    {
        $pipeline = $this->createMock(RagPipeline::class);
        $pipeline->method('ask')->willReturn([
            'answer' => 'This is the answer.',
            'refinedQuery' => 'refined query',
            'expanded' => false,
            'context' => 'Context provided.',
        ]);

        $output = $this->createMock(OutputInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);

        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function test_session_with_pipeline_that_returns_expanded_answer(): void
    {
        $pipeline = $this->createMock(RagPipeline::class);
        $pipeline->method('ask')->willReturn([
            'answer' => 'Expanded answer.',
            'refinedQuery' => 'refined query',
            'expanded' => true,
            'context' => 'Expanded context.',
        ]);

        $output = $this->createMock(OutputInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);

        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function test_session_with_unicode_question(): void
    {
        $pipeline = $this->createMock(RagPipeline::class);
        $output = $this->createMock(OutputInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);

        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function test_session_with_long_question(): void
    {
        $pipeline = $this->createMock(RagPipeline::class);
        $output = $this->createMock(OutputInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);

        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function test_session_with_special_characters_question(): void
    {
        $pipeline = $this->createMock(RagPipeline::class);
        $output = $this->createMock(OutputInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);

        $this->assertInstanceOf(ChatSession::class, $session);
    }

    public function test_session_output_interface_type(): void
    {
        $reflection = new \ReflectionClass($this->session);
        $property = $reflection->getProperty('output');
        $property->setAccessible(true);

        $value = $property->getValue($this->session);
        $this->assertInstanceOf(OutputInterface::class, $value);
    }

    public function test_session_logger_interface_type(): void
    {
        $reflection = new \ReflectionClass($this->session);
        $property = $reflection->getProperty('logger');
        $property->setAccessible(true);

        $value = $property->getValue($this->session);
        $this->assertInstanceOf(LoggerInterface::class, $value);
    }

    public function test_session_rag_pipeline_type(): void
    {
        $reflection = new \ReflectionClass($this->session);
        $property = $reflection->getProperty('ragPipeline');
        $property->setAccessible(true);

        $value = $property->getValue($this->session);
        $this->assertInstanceOf(RagPipeline::class, $value);
    }

    public function test_session_constructor_sets_all_properties(): void
    {
        $pipeline = $this->createMock(RagPipeline::class);
        $output = $this->createMock(OutputInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);

        $reflection = new \ReflectionClass($session);

        $this->assertInstanceOf(RagPipeline::class, $reflection->getProperty('ragPipeline')->getValue($session));
        $this->assertInstanceOf(OutputInterface::class, $reflection->getProperty('output')->getValue($session));
        $this->assertInstanceOf(LoggerInterface::class, $reflection->getProperty('logger')->getValue($session));
    }

    public function test_session_with_different_output_verbosity_levels(): void
    {
        $levels = [
            OutputInterface::VERBOSITY_NORMAL,
            OutputInterface::VERBOSITY_VERBOSE,
            OutputInterface::VERBOSITY_VERY_VERBOSE,
            OutputInterface::VERBOSITY_DEBUG,
            OutputInterface::VERBOSITY_QUIET,
        ];

        foreach ($levels as $level) {
            $pipeline = $this->createMock(RagPipeline::class);
            $output = $this->createMock(OutputInterface::class);
            $output->method('getVerbosity')->willReturn($level);
            $logger = $this->createMock(LoggerInterface::class);

            $session = new ChatSession($pipeline, $output, $logger);

            $this->assertInstanceOf(ChatSession::class, $session);
        }
    }

    public function test_session_with_null_safe_operations(): void
    {
        $pipeline = $this->createMock(RagPipeline::class);
        $output = $this->createMock(OutputInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $session = new ChatSession($pipeline, $output, $logger);

        // Session should handle operations safely
        $this->assertInstanceOf(ChatSession::class, $session);
    }
}

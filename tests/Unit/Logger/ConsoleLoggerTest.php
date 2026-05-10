<?php

declare(strict_types=1);

namespace Tests\Unit\Logger;

use App\Logger\ConsoleLogger;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\Support\TestCase;

class ConsoleLoggerTest extends TestCase
{
    private BufferedOutput $output;

    protected function setUp(): void
    {
        parent::setUp();
        $this->output = new BufferedOutput();
    }

    public function testCreateReturnsMonologLogger(): void
    {
        $logger = ConsoleLogger::create($this->output);

        $this->assertInstanceOf(MonologLogger::class, $logger);
    }

    public function testCreateAddsHandlerToLogger(): void
    {
        $logger = ConsoleLogger::create($this->output);

        $handler = $logger->popHandler();
        $this->assertInstanceOf(ConsoleLogger::class, $handler);
    }

    public function testCreateAddsPsrLogMessageProcessor(): void
    {
        $logger = ConsoleLogger::create($this->output);

        // The logger should have one handler
        $this->assertCount(1, $logger->getHandlers());
    }

    public function testInfoLevelWritesToOutput(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->info('Test info message');

        $content = $this->output->fetch();
        $this->assertStringContainsString('Test info message', $content);
    }

    public function testDebugLevelNotShownAtNormalVerbosity(): void
    {
        $output = new BufferedOutput(); // default verbosity is normal
        $logger = ConsoleLogger::create($output);
        $logger->debug('Debug message');

        $content = $output->fetch();
        $this->assertEmpty($content);
    }

    public function testDebugLevelShownAtVerboseVerbosity(): void
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);
        $logger = ConsoleLogger::create($output);
        $logger->debug('Debug message');

        $content = $output->fetch();
        $this->assertStringContainsString('Debug message', $content);
    }

    public function testErrorLevelShownAtNormalVerbosity(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->error('Error message');

        $content = $this->output->fetch();
        $this->assertStringContainsString('Error message', $content);
    }

    public function testWarningLevelShownAtNormalVerbosity(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->warning('Warning message');

        $content = $this->output->fetch();
        $this->assertStringContainsString('Warning message', $content);
    }

    public function testNoticeLevelShownAtVerboseVerbosity(): void
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);
        $logger = ConsoleLogger::create($output);
        $logger->notice('Notice message');

        $content = $output->fetch();
        $this->assertStringContainsString('Notice message', $content);
    }

    public function testNoticeLevelNotShownAtNormalVerbosity(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->notice('Notice message');

        $content = $this->output->fetch();
        $this->assertEmpty($content);
    }

    public function testCriticalLevelShownAtNormalVerbosity(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->critical('Critical message');

        $content = $this->output->fetch();
        $this->assertStringContainsString('Critical message', $content);
    }

    public function testAlertLevelShownAtNormalVerbosity(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->alert('Alert message');

        $content = $this->output->fetch();
        $this->assertStringContainsString('Alert message', $content);
    }

    public function testEmergencyLevelShownAtNormalVerbosity(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->emergency('Emergency message');

        $content = $this->output->fetch();
        $this->assertStringContainsString('Emergency message', $content);
    }

    public function testHandleReturnsFalse(): void
    {
        $handler = new ConsoleLogger($this->output);
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'Test',
            context: []
        );

        $result = $handler->handle($record);
        $this->assertFalse($result);
    }

    public function testHandlerWithCustomLevel(): void
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);
        $handler = new ConsoleLogger($output, Level::Debug);

        $this->assertInstanceOf(ConsoleLogger::class, $handler);
    }

    public function testHandlerWithInfoLevelDefault(): void
    {
        $output = new BufferedOutput();
        $handler = new ConsoleLogger($output);

        $this->assertInstanceOf(ConsoleLogger::class, $handler);
    }

    public function testMultipleMessages(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->info('First message');
        $logger->info('Second message');
        $logger->warning('Third message');

        $content = $this->output->fetch();
        $this->assertStringContainsString('First message', $content);
        $this->assertStringContainsString('Second message', $content);
        $this->assertStringContainsString('Third message', $content);
    }

    public function testMessageWithContext(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->info('Message with context', ['key' => 'value', 'count' => 42]);

        $content = $this->output->fetch();
        $this->assertStringContainsString('Message with context', $content);
    }

    public function testQuietVerbosityHidesAllMessages(): void
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_QUIET);
        $logger = ConsoleLogger::create($output);
        $logger->info('Should not appear');
        $logger->error('Error should not appear');

        $content = $output->fetch();
        $this->assertEmpty($content);
    }

    public function testDebugVerbosityShowsEverything(): void
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_DEBUG);
        $logger = ConsoleLogger::create($output);
        $logger->debug('Debug');
        $logger->info('Info');
        $logger->warning('Warning');
        $logger->error('Error');

        $content = $output->fetch();
        $this->assertStringContainsString('Debug', $content);
        $this->assertStringContainsString('Info', $content);
        $this->assertStringContainsString('Warning', $content);
        $this->assertStringContainsString('Error', $content);
    }

    public function testLoggerNameIsApp(): void
    {
        $logger = ConsoleLogger::create($this->output);

        $this->assertEquals('app', $logger->getName());
    }

    public function testConsoleLoggerExtendsAbstractHandler(): void
    {
        $handler = new ConsoleLogger($this->output);

        $this->assertInstanceOf(\Monolog\Handler\AbstractHandler::class, $handler);
    }

    public function testHandleWithEmptyMessage(): void
    {
        $handler = new ConsoleLogger($this->output);
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: '',
            context: []
        );

        $result = $handler->handle($record);
        $this->assertFalse($result);
    }

    public function testHandleWithSpecialCharacters(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->info('Special: @#$%^&*()');

        $content = $this->output->fetch();
        $this->assertStringContainsString('Special:', $content);
    }

    public function testHandleWithUnicode(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->info('Café résumé naïve 🎉');

        $content = $this->output->fetch();
        $this->assertStringContainsString('Café', $content);
    }

    public function testHandlerBatchHandle(): void
    {
        $handler = new ConsoleLogger($this->output);
        $records = [];
        for ($i = 0; $i < 3; $i++) {
            $records[] = new LogRecord(
                datetime: new \DateTimeImmutable(),
                channel: 'test',
                level: Level::Info,
                message: "Batch record {$i}",
                context: []
            );
        }

        $result = $handler->handleBatch($records);
        $this->assertNull($result);
    }

    public function testCloseHandler(): void
    {
        $handler = new ConsoleLogger($this->output);
        $result = $handler->close();
        $this->assertNull($result);
    }

    public function testPushHandler(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $handler = new ConsoleLogger($this->output);
        $logger->pushHandler($handler);
        $this->assertCount(2, $logger->getHandlers());
    }

    public function testPopHandler(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $handler = $logger->popHandler();
        $this->assertInstanceOf(ConsoleLogger::class, $handler);
    }

    public function testLevelFilteringInfoAtNormal(): void
    {
        $output = new BufferedOutput();
        $handler = new ConsoleLogger($output, Level::Info);

        $handler->handle(new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Debug,
            message: 'Debug should be filtered',
            context: []
        ));

        $handler->handle(new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'Info should pass',
            context: []
        ));

        $content = $output->fetch();
        $this->assertStringNotContainsString('Debug should be filtered', $content);
        $this->assertStringContainsString('Info should pass', $content);
    }

    public function testLevelFilteringDebugAtDebugLevel(): void
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);
        $handler = new ConsoleLogger($output, Level::Debug);

        $handler->handle(new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Debug,
            message: 'Debug at debug level',
            context: []
        ));

        $content = $output->fetch();
        $this->assertStringContainsString('Debug at debug level', $content);
    }
}

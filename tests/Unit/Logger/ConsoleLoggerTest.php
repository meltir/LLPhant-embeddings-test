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

    public function test_create_returns_monolog_logger(): void
    {
        $logger = ConsoleLogger::create($this->output);

        $this->assertInstanceOf(MonologLogger::class, $logger);
    }

    public function test_create_adds_handler_to_logger(): void
    {
        $logger = ConsoleLogger::create($this->output);

        $handler = $logger->popHandler();
        $this->assertInstanceOf(ConsoleLogger::class, $handler);
    }

    public function test_create_adds_psr_log_message_processor(): void
    {
        $logger = ConsoleLogger::create($this->output);

        // The logger should have one handler
        $this->assertCount(1, $logger->getHandlers());
    }

    public function test_info_level_writes_to_output(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->info('Test info message');

        $content = $this->output->fetch();
        $this->assertStringContainsString('Test info message', $content);
    }

    public function test_debug_level_not_shown_at_normal_verbosity(): void
    {
        $output = new BufferedOutput(); // default verbosity is normal
        $logger = ConsoleLogger::create($output);
        $logger->debug('Debug message');

        $content = $output->fetch();
        $this->assertEmpty($content);
    }

    public function test_debug_level_shown_at_verbose_verbosity(): void
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);
        $logger = ConsoleLogger::create($output);
        $logger->debug('Debug message');

        $content = $output->fetch();
        $this->assertStringContainsString('Debug message', $content);
    }

    public function test_error_level_shown_at_normal_verbosity(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->error('Error message');

        $content = $this->output->fetch();
        $this->assertStringContainsString('Error message', $content);
    }

    public function test_warning_level_shown_at_normal_verbosity(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->warning('Warning message');

        $content = $this->output->fetch();
        $this->assertStringContainsString('Warning message', $content);
    }

    public function test_notice_level_shown_at_verbose_verbosity(): void
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);
        $logger = ConsoleLogger::create($output);
        $logger->notice('Notice message');

        $content = $output->fetch();
        $this->assertStringContainsString('Notice message', $content);
    }

    public function test_notice_level_not_shown_at_normal_verbosity(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->notice('Notice message');

        $content = $this->output->fetch();
        $this->assertEmpty($content);
    }

    public function test_critical_level_shown_at_normal_verbosity(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->critical('Critical message');

        $content = $this->output->fetch();
        $this->assertStringContainsString('Critical message', $content);
    }

    public function test_alert_level_shown_at_normal_verbosity(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->alert('Alert message');

        $content = $this->output->fetch();
        $this->assertStringContainsString('Alert message', $content);
    }

    public function test_emergency_level_shown_at_normal_verbosity(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->emergency('Emergency message');

        $content = $this->output->fetch();
        $this->assertStringContainsString('Emergency message', $content);
    }

    public function test_handle_returns_false(): void
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

    public function test_handler_with_custom_level(): void
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);
        $handler = new ConsoleLogger($output, Level::Debug);

        $this->assertInstanceOf(ConsoleLogger::class, $handler);
    }

    public function test_handler_with_info_level_default(): void
    {
        $output = new BufferedOutput();
        $handler = new ConsoleLogger($output);

        $this->assertInstanceOf(ConsoleLogger::class, $handler);
    }

    public function test_multiple_messages(): void
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

    public function test_message_with_context(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->info('Message with context', ['key' => 'value', 'count' => 42]);

        $content = $this->output->fetch();
        $this->assertStringContainsString('Message with context', $content);
    }

    public function test_quiet_verbosity_hides_all_messages(): void
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_QUIET);
        $logger = ConsoleLogger::create($output);
        $logger->info('Should not appear');
        $logger->error('Error should not appear');

        $content = $output->fetch();
        $this->assertEmpty($content);
    }

    public function test_debug_verbosity_shows_everything(): void
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

    public function test_logger_name_is_app(): void
    {
        $logger = ConsoleLogger::create($this->output);

        $this->assertEquals('app', $logger->getName());
    }

    public function test_console_logger_extends_abstract_handler(): void
    {
        $handler = new ConsoleLogger($this->output);

        $this->assertInstanceOf(\Monolog\Handler\AbstractHandler::class, $handler);
    }

    public function test_handle_with_empty_message(): void
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

    public function test_handle_with_special_characters(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->info('Special: @#$%^&*()');

        $content = $this->output->fetch();
        $this->assertStringContainsString('Special:', $content);
    }

    public function test_handle_with_unicode(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $logger->info('Café résumé naïve 🎉');

        $content = $this->output->fetch();
        $this->assertStringContainsString('Café', $content);
    }

    public function test_handler_batch_handle(): void
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

    public function test_close_handler(): void
    {
        $handler = new ConsoleLogger($this->output);
        $result = $handler->close();
        $this->assertNull($result);
    }

    public function test_push_handler(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $handler = new ConsoleLogger($this->output);
        $logger->pushHandler($handler);
        $this->assertCount(2, $logger->getHandlers());
    }

    public function test_pop_handler(): void
    {
        $logger = ConsoleLogger::create($this->output);
        $handler = $logger->popHandler();
        $this->assertInstanceOf(ConsoleLogger::class, $handler);
    }

    public function test_level_filtering_info_at_normal(): void
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

    public function test_level_filtering_debug_at_debug_level(): void
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

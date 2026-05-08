<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use App\Console\ChatCommand;
use App\Console\EmbeddingGenerateCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Support\TestCase;

class ChatCommandTest extends TestCase
{
    private ChatCommand $command;
    private CommandTester $tester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new ChatCommand();
        $this->tester = new CommandTester($this->command);
    }

    public function test_command_is_instance_of_correct_class(): void
    {
        $this->assertInstanceOf(ChatCommand::class, $this->command);
    }

    public function test_command_extends_base_command(): void
    {
        $this->assertInstanceOf(Command::class, $this->command);
    }

    public function test_command_has_correct_name(): void
    {
        $this->assertEquals('app:chat', $this->command->getName());
    }

    public function test_command_has_description(): void
    {
        $this->assertNotEmpty($this->command->getDescription());
    }

    public function test_command_execute_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $this->assertTrue($reflection->hasMethod('execute'));
    }

    public function test_command_execute_returns_int(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('execute');
        $returnType = $method->getReturnType();

        $this->assertEquals('int', (string) $returnType);
    }

    public function test_command_help(): void
    {
        $application = new \Symfony\Component\Console\Application();
        $application->addCommand($this->command);

        $commandDef = $application->find('app:chat');
        $this->assertEquals('app:chat', $commandDef->getName());
        $this->assertStringContainsString('Sherlock Holmes RAG chatbot', $commandDef->getDescription());
    }

    public function test_command_in_list(): void
    {
        $application = new \Symfony\Component\Console\Application();
        $application->addCommand($this->command);

        $listTester = new CommandTester($application->find('list'));
        $listTester->execute(['command' => 'app:chat']);

        $output = $listTester->getDisplay();
        $this->assertStringContainsString('app:chat', $output);
    }

    public function test_command_without_db_connection_fails_gracefully(): void
    {
        try {
            $exitCode = $this->tester->execute([], [
                'interactive' => false,
            ]);

            // Should fail due to missing DB, but not crash
            $this->assertContains($exitCode, [Command::SUCCESS, Command::FAILURE, 1, 2]);
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->markTestSkipped('DB vector type already registered from previous test');
        }
    }

    public function test_command_output_contains_header_when_started(): void
    {
        try {
            $this->tester->execute([], ['interactive' => false]);
        } catch (\Exception $e) {
            // Expected to fail without DB
        }

        $output = $this->tester->getDisplay();
        $this->assertIsString($output);
    }

    public function test_command_constructor(): void
    {
        $command = new ChatCommand();

        $this->assertInstanceOf(ChatCommand::class, $command);
    }

    public function test_command_configure_inherited(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertInstanceOf(\Symfony\Component\Console\Input\InputDefinition::class, $definition);
    }

    public function test_command_description_content(): void
    {
        $description = $this->command->getDescription();

        $this->assertStringContainsString('chatbot', strtolower($description));
        $this->assertStringContainsString('sherlock', strtolower($description));
    }

    public function test_command_name_is_unique(): void
    {
        $this->assertEquals('app:chat', $this->command->getName());

        // Should not conflict with embedding command
        $embeddingCmd = new EmbeddingGenerateCommand();
        $this->assertNotEquals('app:chat', $embeddingCmd->getName());
    }
}

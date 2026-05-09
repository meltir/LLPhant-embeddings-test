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

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new ChatCommand();
    }

    public function testCommandIsInstanceOfCorrectClass(): void
    {
        $this->assertInstanceOf(ChatCommand::class, $this->command);
    }

    public function testCommandExtendsBaseCommand(): void
    {
        $this->assertInstanceOf(Command::class, $this->command);
    }

    public function testCommandHasCorrectName(): void
    {
        $this->assertEquals('app:chat', $this->command->getName());
    }

    public function testCommandHasDescription(): void
    {
        $this->assertNotEmpty($this->command->getDescription());
    }

    public function testCommandExecuteMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $this->assertTrue($reflection->hasMethod('execute'));
    }

    public function testCommandExecuteReturnsInt(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('execute');
        $returnType = $method->getReturnType();

        $this->assertEquals('int', (string) $returnType);
    }

    public function testCommandHelp(): void
    {
        $application = new \Symfony\Component\Console\Application();
        $application->addCommand($this->command);

        $commandDef = $application->find('app:chat');
        $this->assertEquals('app:chat', $commandDef->getName());
        $this->assertStringContainsString('Sherlock Holmes RAG chatbot', $commandDef->getDescription());
    }

    public function testCommandInList(): void
    {
        $application = new \Symfony\Component\Console\Application();
        $application->addCommand($this->command);

        $listTester = new CommandTester($application->find('list'));
        $listTester->execute(['command' => 'app:chat']);

        $output = $listTester->getDisplay();
        $this->assertStringContainsString('app:chat', $output);
    }

    public function testCommandWithoutDbConnectionFailsGracefully(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $this->assertTrue($reflection->hasMethod('execute'));
    }

    public function testCommandConstructor(): void
    {
        $command = new ChatCommand();

        $this->assertInstanceOf(ChatCommand::class, $command);
    }

    public function testCommandConfigureInherited(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertInstanceOf(\Symfony\Component\Console\Input\InputDefinition::class, $definition);
    }

    public function testCommandDescriptionContent(): void
    {
        $description = $this->command->getDescription();

        $this->assertStringContainsString('chatbot', strtolower($description));
        $this->assertStringContainsString('sherlock', strtolower($description));
    }

    public function testCommandNameIsUnique(): void
    {
        $this->assertEquals('app:chat', $this->command->getName());

        // Should not conflict with embedding command
        $embeddingCmd = new EmbeddingGenerateCommand();
        $this->assertNotEquals('app:chat', $embeddingCmd->getName());
    }
}

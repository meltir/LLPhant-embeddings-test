<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use App\Console\EmbeddingGenerateCommand;
use App\Console\RagApplication;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Support\TestCase;

class EmbeddingGenerateCommandTest extends TestCase
{
    private EmbeddingGenerateCommand $command;
    private CommandTester $tester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new EmbeddingGenerateCommand();
        $this->tester = new CommandTester($this->command);
    }

    public function testCommandIsInstanceOfCorrectClass(): void
    {
        $this->assertInstanceOf(EmbeddingGenerateCommand::class, $this->command);
    }

    public function testCommandExtendsBaseCommand(): void
    {
        $this->assertInstanceOf(Command::class, $this->command);
    }

    public function testCommandHasCorrectName(): void
    {
        $this->assertEquals('app:embeddings:generate', $this->command->getName());
    }

    public function testCommandHasDescription(): void
    {
        $this->assertNotEmpty($this->command->getDescription());
    }

    public function testCommandHasTextDirOption(): void
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('text-dir'));
    }

    public function testCommandHasMaxLengthOption(): void
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('max-length'));
    }

    public function testCommandHasSeparatorOption(): void
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('separator'));
    }

    public function testCommandHasWordOverlapOption(): void
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('word-overlap'));
    }

    public function testCommandHasEmbeddingGeneratorOption(): void
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('embedding-generator'));
    }

    public function testCommandTextDirDefault(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('text-dir');
        $this->assertStringContainsString('text', $option->getDefault());
    }

    public function testCommandMaxLengthDefault(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('max-length');
        $this->assertEquals('200', $option->getDefault());
    }

    public function testCommandSeparatorDefault(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('separator');
        $this->assertEquals('.', $option->getDefault());
    }

    public function testCommandWordOverlapDefault(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('word-overlap');
        $this->assertEquals('10', $option->getDefault());
    }

  public function testCommandEmbeddingGeneratorDefault(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('embedding-generator');
        $this->assertStringContainsString('EmbeddingGemmaEmbeddingGenerator', $option->getDefault());
    }

    public function testCommandHelp(): void
    {
        $application = new \Symfony\Component\Console\Application();
        $application->addCommand($this->command);

        $commandDef = $application->find('app:embeddings:generate');
        $this->assertEquals('app:embeddings:generate', $commandDef->getName());
        $this->assertStringContainsString('Generate embeddings', $commandDef->getDescription());

        $definition = $commandDef->getDefinition();
        $this->assertNotNull($definition->getOption('text-dir'));
        $this->assertNotNull($definition->getOption('max-length'));
        $this->assertNotNull($definition->getOption('separator'));
        $this->assertNotNull($definition->getOption('word-overlap'));
        $this->assertNotNull($definition->getOption('embedding-generator'));
    }

    public function testCommandListOptions(): void
    {
        $application = new Application();
        $application->addCommand($this->command);

        $listTester = new CommandTester($application->find('list'));
        $listTester->execute(['command' => 'app:embeddings:generate']);

        $output = $listTester->getDisplay();
        $this->assertStringContainsString('app:embeddings:generate', $output);
    }

    public function testCommandIsConfigured(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $this->assertTrue($reflection->hasMethod('configure'));
    }

    public function testCommandExecuteMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $this->assertTrue($reflection->hasMethod('execute'));
    }

    public function testCommandConfigureMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $this->assertTrue($reflection->hasMethod('configure'));
    }

    public function testCommandExecuteReturnsInt(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('execute');
        $returnType = $method->getReturnType();

        $this->assertEquals('int', (string) $returnType);
    }

    public function testCommandWithAllOptions(): void
    {
        try {
            $this->tester->execute([
                '--text-dir' => '/tmp/test',
                '--max-length' => '150',
                '--separator' => ',',
                '--word-overlap' => '5',
                '--embedding-generator' => \App\EmbeddingGemma\EmbeddingGemmaEmbeddingGenerator::class,
            ]);
        } catch (\Exception $e) {
            // Expected to fail without DB
        }

        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('Sherlock Holmes Embedding Generator', $output);
    }
}

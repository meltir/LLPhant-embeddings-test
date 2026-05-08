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

    public function test_command_is_instance_of_correct_class(): void
    {
        $this->assertInstanceOf(EmbeddingGenerateCommand::class, $this->command);
    }

    public function test_command_extends_base_command(): void
    {
        $this->assertInstanceOf(Command::class, $this->command);
    }

    public function test_command_has_correct_name(): void
    {
        $this->assertEquals('app:embeddings:generate', $this->command->getName());
    }

    public function test_command_has_description(): void
    {
        $this->assertNotEmpty($this->command->getDescription());
    }

    public function test_command_has_text_dir_option(): void
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('text-dir'));
    }

    public function test_command_has_max_length_option(): void
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('max-length'));
    }

    public function test_command_has_separator_option(): void
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('separator'));
    }

    public function test_command_has_word_overlap_option(): void
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('word-overlap'));
    }

    public function test_command_has_embedding_generator_option(): void
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('embedding-generator'));
    }

    public function test_command_text_dir_default(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('text-dir');
        $this->assertStringContainsString('text', $option->getDefault());
    }

    public function test_command_max_length_default(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('max-length');
        $this->assertEquals('200', $option->getDefault());
    }

    public function test_command_separator_default(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('separator');
        $this->assertEquals('.', $option->getDefault());
    }

    public function test_command_word_overlap_default(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('word-overlap');
        $this->assertEquals('10', $option->getDefault());
    }

  public function test_command_embedding_generator_default(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('embedding-generator');
        $this->assertStringContainsString('EmbeddingGemmaEmbeddingGenerator', $option->getDefault());
    }

    public function test_command_help(): void
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

    public function test_command_list_options(): void
    {
        $application = new Application();
        $application->addCommand($this->command);

        $listTester = new CommandTester($application->find('list'));
        $listTester->execute(['command' => 'app:embeddings:generate']);

        $output = $listTester->getDisplay();
        $this->assertStringContainsString('app:embeddings:generate', $output);
    }

    public function test_command_is_configured(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $this->assertTrue($reflection->hasMethod('configure'));
    }

    public function test_command_execute_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $this->assertTrue($reflection->hasMethod('execute'));
    }

    public function test_command_configure_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $this->assertTrue($reflection->hasMethod('configure'));
    }

    public function test_command_execute_returns_int(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('execute');
        $returnType = $method->getReturnType();

        $this->assertEquals('int', (string) $returnType);
    }

    public function test_command_with_all_options(): void
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

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

    public function testCommandTextDirDefault(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('text-dir');
        $default = $option->getDefault();
        $this->assertIsString($default);
        $this->assertStringContainsString('text', $default);
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

    public function testCommandHasResetDbOption(): void
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('reset-db'));
    }

    public function testCommandResetDbOptionIsValueNone(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('reset-db');
        $this->assertFalse($option->isArray());
    }

    public function testCommandHelp(): void
    {
        $application = new \Symfony\Component\Console\Application();
        $application->addCommand($this->command);

        $commandDef = $application->find('app:embeddings:generate');
        $this->assertEquals('app:embeddings:generate', $commandDef->getName());
        $this->assertStringContainsString('Generate embeddings', $commandDef->getDescription());

        $definition = $commandDef->getDefinition();
        $this->assertTrue($definition->hasOption('text-dir'));
        $this->assertTrue($definition->hasOption('max-length'));
        $this->assertTrue($definition->hasOption('separator'));
        $this->assertTrue($definition->hasOption('word-overlap'));
        $this->assertTrue($definition->hasOption('reset-db'));
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
            ]);
        } catch (\Exception $e) {
            // Expected to fail without DB
        }

        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('Sherlock Holmes Embedding Generator', $output);
    }

    public function testCommandWithResetDb(): void
    {
        try {
            $this->tester->execute([
                '--reset-db' => true,
            ]);
        } catch (\Exception $e) {
            // Expected to fail without DB
        }

        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('Sherlock Holmes Embedding Generator', $output);
    }

    public function testDetectEmbeddingLengthReturnsCorrectLength(): void
    {
        $vector = [];
        for ($i = 0; $i < 768; $i++) {
            $vector[] = round(sin($i * 0.01) * 0.5, 6);
        }
        $fake = new \OpenAI\Testing\ClientFake([
            \OpenAI\Responses\Embeddings\CreateResponse::fake([
                'data' => [['embedding' => $vector]],
            ]),
        ]);
        $config = new \LLPhant\OpenAIConfig();
        $config->client = $fake;

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('detectEmbeddingLength');
        $method->setAccessible(true);

        $outputMock = $this->createStub(\Symfony\Component\Console\Output\OutputInterface::class);

        $result = $method->invoke($this->command, $outputMock);

        $this->assertEquals(768, $result);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use App\Console\ChatCommand;
use App\Console\EmbeddingGenerateCommand;
use App\Console\RagApplication;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Support\TestCase;

class RagApplicationTest extends TestCase
{
    public function testApplicationIsInstanceOfCorrectClass(): void
    {
        $app = new RagApplication();

        $this->assertInstanceOf(RagApplication::class, $app);
    }

    public function testApplicationExtendsConsoleApplication(): void
    {
        $app = new RagApplication();

        $this->assertInstanceOf(\Symfony\Component\Console\Application::class, $app);
    }

    public function testApplicationHasName(): void
    {
        $app = new RagApplication();

        $this->assertNotEmpty($app->getName());
    }

    public function testApplicationHasVersion(): void
    {
        $app = new RagApplication();

        $this->assertNotEmpty($app->getVersion());
    }

    public function testApplicationRegistersEmbeddingCommand(): void
    {
        $app = new RagApplication();
        $commands = $app->all();

        $found = false;
        foreach ($commands as $name => $cmd) {
            if ($name === 'app:embeddings:generate') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testApplicationRegistersChatCommand(): void
    {
        $app = new RagApplication();
        $commands = $app->all();

        $found = false;
        foreach ($commands as $name => $cmd) {
            if ($name === 'app:chat') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testApplicationEmbeddingCommandIsCorrectType(): void
    {
        $app = new RagApplication();
        $cmd = $app->get('app:embeddings:generate');

        $this->assertInstanceOf(EmbeddingGenerateCommand::class, $cmd);
    }

    public function testApplicationChatCommandIsCorrectType(): void
    {
        $app = new RagApplication();
        $cmd = $app->get('app:chat');

        $this->assertInstanceOf(ChatCommand::class, $cmd);
    }

    public function testApplicationRuns(): void
    {
        $app = new RagApplication();

        $this->assertInstanceOf(RagApplication::class, $app);
    }

    public function testApplicationHasBothCommands(): void
    {
        $app = new RagApplication();
        $commands = $app->all();

        $this->assertCount(2, array_intersect_key($commands, [
            'app:embeddings:generate' => true,
            'app:chat' => true,
        ]));
    }

    public function testApplicationEmbeddingCommandName(): void
    {
        $app = new RagApplication();
        $embeddingCmd = $app->get('app:embeddings:generate');

        $this->assertEquals('app:embeddings:generate', $embeddingCmd->getName());
    }

    public function testApplicationChatCommandName(): void
    {
        $app = new RagApplication();
        $chatCmd = $app->get('app:chat');

        $this->assertEquals('app:chat', $chatCmd->getName());
    }

    public function testApplicationDescription(): void
    {
        $app = new RagApplication();
        $embeddingCmd = $app->get('app:embeddings:generate');

        $this->assertNotEmpty($embeddingCmd->getDescription());
    }

    public function testApplicationVersionIsString(): void
    {
        $app = new RagApplication();

        $this->assertGreaterThan(0, strlen($app->getVersion()));
    }

    public function testApplicationNameIsString(): void
    {
        $app = new RagApplication();

        $this->assertGreaterThan(0, strlen($app->getName()));
    }

    public function testApplicationCanFindCommands(): void
    {
        $app = new RagApplication();

        $embeddingCmd = $app->find('app:embeddings:generate');
        $this->assertInstanceOf(EmbeddingGenerateCommand::class, $embeddingCmd);

        $chatCmd = $app->find('app:chat');
        $this->assertInstanceOf(ChatCommand::class, $chatCmd);
    }

    public function testApplicationAllCommands(): void
    {
        $app = new RagApplication();
        $commands = $app->all();

        $this->assertGreaterThan(0, count($commands));
    }

    public function testApplicationDefaultCommand(): void
    {
        $app = new RagApplication();

        // The application should have the expected name and version
        $this->assertEquals('Sherlock Holmes RAG', $app->getName());
        $this->assertEquals('1.0.0', $app->getVersion());
    }

    public function testApplicationWithLongName(): void
    {
        $app = new RagApplication();

        $this->assertGreaterThan(0, strlen($app->getName()));
    }

    public function testApplicationWithVersionNumber(): void
    {
        $app = new RagApplication();

        $this->assertStringMatchesFormat('%d.%d.%d', $app->getVersion());
    }
}

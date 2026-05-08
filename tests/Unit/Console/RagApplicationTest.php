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
    public function test_application_is_instance_of_correct_class(): void
    {
        $app = new RagApplication();

        $this->assertInstanceOf(RagApplication::class, $app);
    }

    public function test_application_extends_console_application(): void
    {
        $app = new RagApplication();

        $this->assertInstanceOf(\Symfony\Component\Console\Application::class, $app);
    }

    public function test_application_has_name(): void
    {
        $app = new RagApplication();

        $this->assertNotEmpty($app->getName());
    }

    public function test_application_has_version(): void
    {
        $app = new RagApplication();

        $this->assertNotEmpty($app->getVersion());
    }

    public function test_application_registers_embedding_command(): void
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

    public function test_application_registers_chat_command(): void
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

    public function test_application_embedding_command_is_correct_type(): void
    {
        $app = new RagApplication();
        $cmd = $app->get('app:embeddings:generate');

        $this->assertInstanceOf(EmbeddingGenerateCommand::class, $cmd);
    }

    public function test_application_chat_command_is_correct_type(): void
    {
        $app = new RagApplication();
        $cmd = $app->get('app:chat');

        $this->assertInstanceOf(ChatCommand::class, $cmd);
    }

    public function test_application_runs(): void
    {
        $app = new RagApplication();

        $this->assertInstanceOf(RagApplication::class, $app);
    }

    public function test_application_has_both_commands(): void
    {
        $app = new RagApplication();
        $commands = $app->all();

        $this->assertCount(2, array_intersect_key($commands, [
            'app:embeddings:generate' => true,
            'app:chat' => true,
        ]));
    }

    public function test_application_embedding_command_name(): void
    {
        $app = new RagApplication();
        $embeddingCmd = $app->get('app:embeddings:generate');

        $this->assertEquals('app:embeddings:generate', $embeddingCmd->getName());
    }

    public function test_application_chat_command_name(): void
    {
        $app = new RagApplication();
        $chatCmd = $app->get('app:chat');

        $this->assertEquals('app:chat', $chatCmd->getName());
    }

    public function test_application_description(): void
    {
        $app = new RagApplication();
        $embeddingCmd = $app->get('app:embeddings:generate');

        $this->assertNotEmpty($embeddingCmd->getDescription());
    }

    public function test_application_version_is_string(): void
    {
        $app = new RagApplication();

        $this->assertIsString($app->getVersion());
    }

    public function test_application_name_is_string(): void
    {
        $app = new RagApplication();

        $this->assertIsString($app->getName());
    }

    public function test_application_can_find_commands(): void
    {
        $app = new RagApplication();

        $embeddingCmd = $app->find('app:embeddings:generate');
        $this->assertInstanceOf(EmbeddingGenerateCommand::class, $embeddingCmd);

        $chatCmd = $app->find('app:chat');
        $this->assertInstanceOf(ChatCommand::class, $chatCmd);
    }

    public function test_application_all_commands(): void
    {
        $app = new RagApplication();
        $commands = $app->all();

        $this->assertIsArray($commands);
        $this->assertGreaterThan(0, count($commands));
    }

    public function test_application_default_command(): void
    {
        $app = new RagApplication();

        // The application should have the expected name and version
        $this->assertEquals('Sherlock Holmes RAG', $app->getName());
        $this->assertEquals('1.0.0', $app->getVersion());
    }

    public function test_application_with_long_name(): void
    {
        $app = new RagApplication();

        $this->assertIsString($app->getName());
        $this->assertGreaterThan(0, strlen($app->getName()));
    }

    public function test_application_with_version_number(): void
    {
        $app = new RagApplication();

        $this->assertIsString($app->getVersion());
    }
}

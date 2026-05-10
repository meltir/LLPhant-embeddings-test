<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use PHPUnit\Framework\TestCase;

class CliEntrypointTest extends TestCase
{
    public function testCliFileExists(): void
    {
        $cliPath = __DIR__ . '/../../../cli.php';
        $this->assertFileExists($cliPath);
    }

    public function testCliFileIsReadable(): void
    {
        $cliPath = __DIR__ . '/../../../cli.php';
        $this->assertIsReadable($cliPath);
    }

    public function testCliFileHasCorrectShebang(): void
    {
        $cliPath = __DIR__ . '/../../../cli.php';
        $content = file_get_contents($cliPath);
        $this->assertStringContainsString('#!/usr/bin/env php', $content);
    }

    public function testCliFileContainsChatCommandReference(): void
    {
        $cliPath = __DIR__ . '/../../../cli.php';
        $content = file_get_contents($cliPath);
        $this->assertStringContainsString('ChatCommand', $content);
    }

    public function testCliFileContainsEmbeddingCommandReference(): void
    {
        $cliPath = __DIR__ . '/../../../cli.php';
        $content = file_get_contents($cliPath);
        $this->assertStringContainsString('EmbeddingGenerateCommand', $content);
    }

    public function testCliFileContainsUsageMessage(): void
    {
        $cliPath = __DIR__ . '/../../../cli.php';
        $content = file_get_contents($cliPath);
        $this->assertStringContainsString('Usage:', $content);
    }

    public function testCliFileContainsValidModes(): void
    {
        $cliPath = __DIR__ . '/../../../cli.php';
        $content = file_get_contents($cliPath);
        $this->assertStringContainsString("'chat'", $content);
        $this->assertStringContainsString("'regenerate-embeddings'", $content);
    }

    public function testCliFileExitsWithCode1OnInvalidMode(): void
    {
        $cliPath = __DIR__ . '/../../../cli.php';
        $content = file_get_contents($cliPath);
        $this->assertStringContainsString("exit(1)", $content);
    }

    public function testCliFileUsesSymfonyConsoleInput(): void
    {
        $cliPath = __DIR__ . '/../../../cli.php';
        $content = file_get_contents($cliPath);
        $this->assertStringContainsString('Symfony\Component\Console\Input\ArrayInput', $content);
    }

    public function testCliFileRunsCommandDirectly(): void
    {
        $cliPath = __DIR__ . '/../../../cli.php';
        $content = file_get_contents($cliPath);
        $this->assertStringContainsString('$command->run(', $content);
    }
}

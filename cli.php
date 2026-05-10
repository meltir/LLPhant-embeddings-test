#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Console\ChatCommand;
use App\Console\EmbeddingGenerateCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

$scriptName = basename($_SERVER['SCRIPT_NAME'] ?? 'cli.php');
$mode = $argv[1] ?? '';

$validModes = ['chat', 'regenerate-embeddings'];

if ($mode === '' || in_array($mode, $validModes, true) === false) {
    echo "Usage: php {$scriptName} {chat|regenerate-embeddings} [options]\n\n";
    echo "Commands:\n";
    echo "  chat                        Start the Sherlock Holmes RAG chatbot\n";
    echo "  regenerate-embeddings       Generate embeddings for all documents\n";
    echo "\nOptions for regenerate-embeddings:\n";
    echo "  --text-dir=PATH             Directory containing text files (default: text)\n";
    echo "  --max-length=NUM            Maximum chunk length (default: 200)\n";
    echo "  --separator=CHAR            Chunk separator (default: .)\n";
    echo "  --word-overlap=NUM          Word overlap between chunks (default: 10)\n";
    echo "  --reset-db                  Drop and recreate the chunks table\n";
    exit(1);
}

$command = match ($mode) {
    'chat' => new ChatCommand(),
    'regenerate-embeddings' => new EmbeddingGenerateCommand(),
};

$definition = $command->getDefinition();
$input = new ArrayInput($definition->getArguments(), $definition);
$output = new ConsoleOutput();
$command->run($input, $output);

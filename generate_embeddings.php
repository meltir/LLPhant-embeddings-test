<?php

declare(strict_types=1);

require_once __DIR__ . "/vendor/autoload.php";

use App\Console\EmbeddingGenerateCommand;
use Symfony\Component\Console\Application;

$command = new EmbeddingGenerateCommand(__DIR__ . '/text');
$app = new Application();
$app->addCommand($command);
$app->setDefaultCommand($command->getName(), true);
$app->setAutoExit(false);
$app->run();

<?php

declare(strict_types=1);

require_once __DIR__ . "/vendor/autoload.php";

use App\Console\ChatCommand;
use Symfony\Component\Console\Application;

$command = new ChatCommand();
$app = new Application();
$app->addCommand($command);
$app->setDefaultCommand($command->getName(), true);
$app->setAutoExit(false);
$app->run();

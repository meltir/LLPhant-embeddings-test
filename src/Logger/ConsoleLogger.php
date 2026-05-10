<?php

declare(strict_types=1);

namespace App\Logger;

use Monolog\Handler\AbstractHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Logger as MonologLogger;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleLogger extends AbstractHandler
{
    public static function create(OutputInterface $output): MonologLogger
    {
        $logger = new MonologLogger('app');
        $logger->pushHandler(new self($output));
        $logger->pushProcessor(new PsrLogMessageProcessor());

        return $logger;
    }

    public function __construct(
        OutputInterface $output,
        Level $level = Level::Debug
    ) {
        parent::__construct($level);
        $this->output = $output;
    }

    public function handle(LogRecord $record): bool
    {
        $verbosity = match ($record->level) {
            Level::Debug => OutputInterface::VERBOSITY_VERBOSE,
            Level::Notice => OutputInterface::VERBOSITY_VERBOSE,
            default => OutputInterface::VERBOSITY_NORMAL,
        };

        if ($this->output->getVerbosity() < $verbosity) {
            return false;
        }

        $this->output->writeln($record->message, $verbosity);

        return false;
    }

    private OutputInterface $output;
}

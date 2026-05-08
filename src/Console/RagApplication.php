<?php

declare(strict_types=1);

namespace App\Console;

use Symfony\Component\Console\Application;

class RagApplication extends Application
{
    public function __construct()
    {
        parent::__construct('Sherlock Holmes RAG', '1.0.0');

        $this->addCommand(new EmbeddingGenerateCommand());
        $this->addCommand(new ChatCommand());
    }
}

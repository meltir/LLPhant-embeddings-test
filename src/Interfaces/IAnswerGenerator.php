<?php

declare(strict_types=1);

namespace App\Interfaces;

interface IAnswerGenerator
{
    public function generate(string $question, string $context): string;
}

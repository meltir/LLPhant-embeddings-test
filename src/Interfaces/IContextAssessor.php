<?php

declare(strict_types=1);

namespace App\Interfaces;

interface IContextAssessor
{
    /**
     * @return 'ENOUGH'|'NOT_ENOUGH'
     */
    public function assess(string $question, string $context): string;
}

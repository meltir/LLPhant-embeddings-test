<?php

declare(strict_types=1);

namespace App\Interfaces;

interface IQueryRefiner
{
    public function refine(string $question): string;
}

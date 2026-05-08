<?php

declare(strict_types=1);

namespace App\Pipeline;

class EmbeddingStats
{
    public int $total = 0;
    public int $inserted = 0;
    public int $skipped = 0;
}

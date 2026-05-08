<?php

declare(strict_types=1);

namespace App\Interfaces;

use LLPhant\Embeddings\Document;

interface IVectorSearch
{
    /**
     * @param float[] $embedding
     * @return Document[]
     */
    public function search(array $embedding, int $k = 4): array;
}

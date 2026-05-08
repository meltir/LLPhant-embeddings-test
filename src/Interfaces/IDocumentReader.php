<?php

declare(strict_types=1);

namespace App\Interfaces;

use LLPhant\Embeddings\Document;

interface IDocumentReader
{
    /**
     * @return Document[]
     */
    public function read(string $directory): array;
}

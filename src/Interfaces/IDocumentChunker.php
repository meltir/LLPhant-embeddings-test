<?php

declare(strict_types=1);

namespace App\Interfaces;

use LLPhant\Embeddings\Document;

interface IDocumentChunker
{
    /**
     * @return Document[]
     */
    public function chunk(Document $document, int $maxLength, string $separator, int $wordOverlap): array;
}

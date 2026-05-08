<?php

declare(strict_types=1);

namespace App\Documents;

use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use App\Interfaces\IDocumentChunker;

class LLPhantDocumentChunker implements IDocumentChunker
{
    /**
     * @return Document[]
     */
    public function chunk(Document $document, int $maxLength, string $separator, int $wordOverlap): array
    {
        return DocumentSplitter::splitDocument($document, $maxLength, $separator, $wordOverlap);
    }
}

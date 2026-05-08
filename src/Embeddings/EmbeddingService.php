<?php

declare(strict_types=1);

namespace App\Embeddings;

use LLPhant\Embeddings\Document;
use App\EmbeddingGemma\EmbeddingGemmaEmbeddingGenerator;

class EmbeddingService
{
    public function embed(Document $document): Document
    {
        $generator = new EmbeddingGemmaEmbeddingGenerator();
        $generator->embedDocument($document);

        return $document;
    }

    /**
     * @return float[]
     */
    public function embedText(string $text): array
    {
        $generator = new EmbeddingGemmaEmbeddingGenerator();

        return $generator->embedText($text);
    }
}

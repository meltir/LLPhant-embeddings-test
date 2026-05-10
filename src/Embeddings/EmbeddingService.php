<?php

declare(strict_types=1);

namespace App\Embeddings;

use LLPhant\Embeddings\Document;
use App\EmbeddingGenerator\GenericEmbeddingGenerator;

class EmbeddingService
{
    public function __construct(
        private readonly ?GenericEmbeddingGenerator $generator = null,
    ) {
    }

    public function embed(Document $document): Document
    {
        $gen = $this->generator ?? new GenericEmbeddingGenerator();
        $gen->embedDocument($document);

        return $document;
    }

    /**
     * @return float[]
     */
    public function embedText(string $text): array
    {
        $gen = $this->generator ?? new GenericEmbeddingGenerator();

        return $gen->embedText($text);
    }
}

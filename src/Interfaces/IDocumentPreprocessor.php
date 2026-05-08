<?php

declare(strict_types=1);

namespace App\Interfaces;

use LLPhant\Embeddings\Document;

interface IDocumentPreprocessor
{
    public function preprocess(Document $document, string $title): Document;
}

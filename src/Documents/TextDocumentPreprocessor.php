<?php

declare(strict_types=1);

namespace App\Documents;

use LLPhant\Embeddings\Document;
use App\Interfaces\IDocumentPreprocessor;

class TextDocumentPreprocessor implements IDocumentPreprocessor
{
    public function preprocess(Document $document, string $title): Document
    {
        $content = $document->content ?? '';

        $footerPattern = "/\n\s*----------\s*\n.*?This text comes from the collection/s";
        $content = preg_replace($footerPattern, '', $content) ?? $content;

        $content = preg_replace('/\s+$/', '', $content) ?? $content;
        $content = preg_replace("/\n{3,}/", "\n\n", $content) ?? $content;
        $content = ltrim($content);

        $document->content = $content;
        $document->sourceName = $title;

        return $document;
    }
}

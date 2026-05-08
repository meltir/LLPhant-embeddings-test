<?php

declare(strict_types=1);

namespace App\Documents;

use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\Document;
use App\Interfaces\IDocumentReader;

class FileDocumentReader implements IDocumentReader
{
    /**
     * @return Document[]
     */
    public function read(string $directory): array
    {
        $reader = new FileDataReader($directory);

        return $reader->getDocuments();
    }
}

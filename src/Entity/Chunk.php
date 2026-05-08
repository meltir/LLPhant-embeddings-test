<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use LLPhant\Embeddings\VectorStores\Doctrine\DoctrineEmbeddingEntityBase;
use LLPhant\Embeddings\VectorStores\Doctrine\VectorType;

#[ORM\Entity]
#[ORM\Table(name: 'chunks')]
class Chunk extends DoctrineEmbeddingEntityBase
{
    // Map sourceType to source_type (the DB column)
    #[ORM\Column(type: 'string', length: 255, name: 'source_type')]
    public string $sourceType = 'file';

    // Map sourceName to novel_title (the DB column for story title)
    #[ORM\Column(type: 'string', length: 255, name: 'novel_title')]
    public string $sourceName = 'manual';

    #[ORM\Column(type: 'integer', name: 'chunk_index')]
    public int $chunkIndex;

    // Override vector length from 1536 (base) to 768 (embeddinggemma)
    #[ORM\Column(type: VectorType::VECTOR, length: 768)]
    public ?array $embedding;
}

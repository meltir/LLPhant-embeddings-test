<?php

declare(strict_types=1);

namespace App\Search;

use Doctrine\ORM\EntityManagerInterface;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\VectorStores\Doctrine\DoctrineVectorStore;
use App\Interfaces\IVectorSearch;

class VectorSearchService implements IVectorSearch
{
    /**
     * @param class-string<\LLPhant\Embeddings\VectorStores\Doctrine\DoctrineEmbeddingEntityBase> $entityClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $entityClass = \App\Entity\Chunk::class
    ) {
    }

    /**
     * @param float[] $embedding
     * @return Document[]
     */
    public function search(array $embedding, int $k = 4): array
    {
        $vectorStore = new DoctrineVectorStore($this->entityManager, $this->entityClass);

        return $vectorStore->similaritySearch($embedding, $k);
    }
}

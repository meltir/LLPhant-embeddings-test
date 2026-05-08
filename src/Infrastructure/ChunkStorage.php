<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Entity\Chunk;
use Doctrine\ORM\EntityManagerInterface;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\VectorStores\Doctrine\DoctrineVectorStore;

class ChunkStorage
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DoctrineVectorStore $vectorStore
    ) {
    }

    public function exists(string $sourceName, int $chunkIndex, string $content): bool
    {
        $existing = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(Chunk::class, 'c')
            ->where('c.sourceName = :sourceName')
            ->andWhere('c.chunkIndex = :chunkIndex')
            ->andWhere('c.content = :content')
            ->setParameter('sourceName', $sourceName)
            ->setParameter('chunkIndex', $chunkIndex)
            ->setParameter('content', $content)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $existing !== null;
    }

    public function persist(Document $document, string $sourceName, int $chunkIndex): void
    {
        $chunk = new Chunk();
        $chunk->content = $document->content;
        $chunk->embedding = $document->embedding;
        $chunk->sourceName = $sourceName;
        $chunk->sourceType = 'file';
        $chunk->chunkIndex = $chunkIndex;

        $this->vectorStore->addDocument($chunk);
    }

    public function clear(): void
    {
        $this->entityManager->clear();
        gc_collect_cycles();
    }

    public function count(): int
    {
        return (int) $this->entityManager
            ->createQuery('SELECT COUNT(c) FROM App\Entity\Chunk c')
            ->getSingleScalarResult();
    }
}

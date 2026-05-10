<?php

declare(strict_types=1);

namespace App\Chat;

use LLPhant\Embeddings\Document;
use App\Interfaces\IAnswerGenerator;
use App\Interfaces\IContextAssessor;
use App\Interfaces\IQueryRefiner;
use App\Interfaces\IVectorSearch;

class RagPipeline
{
    public function __construct(
        private readonly IVectorSearch $vectorSearch,
        private readonly IQueryRefiner $queryRefiner,
        private readonly IContextAssessor $contextAssessor,
        private readonly IAnswerGenerator $answerGenerator,
        private readonly ?\App\EmbeddingGenerator\GenericEmbeddingGenerator $embeddingGenerator = null,
    ) {
    }

    /**
     * @return array{answer: string, refinedQuery: string, expanded: bool, context: string}
     */
    public function ask(string $question, int $initialK = 4, int $expandedK = 12): array
    {
        $refinedQuery = $this->queryRefiner->refine($question);

        $context = $this->buildContext(
            $this->vectorSearch->search($this->extractEmbedding($refinedQuery), $initialK)
        );

        $expanded = false;
        if ($this->contextAssessor->assess($question, $context) === 'NOT_ENOUGH') {
            $expanded = true;
            $context = $this->buildContext(
                $this->vectorSearch->search($this->extractEmbedding($refinedQuery), $expandedK)
            );
        }

        $answer = $this->answerGenerator->generate($question, $context);

        return [
            'answer' => $answer,
            'refinedQuery' => $refinedQuery,
            'expanded' => $expanded,
            'context' => $context,
        ];
    }

    /**
     * @return float[]
     */
    private function extractEmbedding(string $text): array
    {
        $doc = new Document();
        $doc->content = $text;

        $generator = $this->embeddingGenerator ?? new \App\EmbeddingGenerator\GenericEmbeddingGenerator();
        $generator->embedDocument($doc);

        return $doc->embedding ?? [];
    }

    /**
     * @param Document[] $documents
     */
    private function buildContext(array $documents): string
    {
        $context = '';
        foreach ($documents as $doc) {
            $context .= "[Story: {$doc->sourceName} | Chunk: {$doc->chunkNumber}] {$doc->content}\n\n";
        }

        return $context;
    }
}

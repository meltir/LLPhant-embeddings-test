<?php

declare(strict_types=1);

namespace App\EmbeddingGenerator;

use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\AbstractOpenAIEmbeddingGenerator;
use LLPhant\OpenAIConfig;
use OpenAI\Contracts\ClientContract;

final class GenericEmbeddingGenerator extends AbstractOpenAIEmbeddingGenerator
{
    private int $embeddingLength;

    private function getRequiredEnv(string $key): string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            throw new \RuntimeException("Environment variable '{$key}' is not set.");
        }
        return $value;
    }

    /**
     * @throws \Exception
     */
    public function __construct(?OpenAIConfig $config = null)
    {
        if ($config instanceof OpenAIConfig && $config->client instanceof ClientContract) {
            $this->client = $config->client;

            return;
        }

        $apiKey = getenv('EMBEDDING_API_KEY');
        $baseUrl = getenv('EMBEDDING_URL');

        if ($apiKey === false || $apiKey === '' || $baseUrl === false || $baseUrl === '') {
            throw new \RuntimeException('EMBEDDING_API_KEY and EMBEDDING_URL environment variables must be set.');
        }

        $this->client = \OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri($baseUrl)
            ->make();

        $this->uri = $baseUrl . '/embeddings';
        $this->apiKey = $apiKey;

        $this->detectEmbeddingLength();
    }

    private function detectEmbeddingLength(): void
    {
        $presetText = 'the quick brown fox jumped over the lazy dog';
        $embedding = $this->embedText($presetText);
        $this->embeddingLength = count($embedding);
    }

    public function getEmbeddingLength(): int
    {
        if (!isset($this->embeddingLength)) {
            $this->detectEmbeddingLength();
        }

        return $this->embeddingLength;
    }

    public function getModelName(): string
    {
        $model = getenv('EMBEDDING_MODEL');
        if ($model === false || $model === '') {
            throw new \RuntimeException("Environment variable 'EMBEDDING_MODEL' is not set.");
        }

        return $model;
    }
}

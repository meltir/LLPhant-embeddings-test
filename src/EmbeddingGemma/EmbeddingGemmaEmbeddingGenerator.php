<?php

declare(strict_types=1);

namespace App\EmbeddingGemma;

use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\AbstractOpenAIEmbeddingGenerator;
use LLPhant\OpenAIConfig;
use OpenAI\Contracts\ClientContract;

final class EmbeddingGemmaEmbeddingGenerator extends AbstractOpenAIEmbeddingGenerator
{
    /**
     * @throws \Exception
     */
    public function __construct(?OpenAIConfig $config = null)
    {
        if ($config instanceof OpenAIConfig && $config->client instanceof ClientContract) {
            $this->client = $config->client;

            return;
        }

        $apiKey = $config?->apiKey ?: (getenv('LLM_API_KEY') ?: 'sk-not-needed');
        $baseUrl = getenv('LLM_URL') ?: 'http://192.168.1.20:8001/v1';

        $this->client = \OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri($baseUrl)
            ->make();

        $this->uri = $baseUrl . '/embeddings';
        $this->apiKey = $apiKey;
    }

    public function getEmbeddingLength(): int
    {
        return 768;
    }

    public function getModelName(): string
    {
        return 'unsloth/embeddinggemma-300m-GGUF:Q4_0';
    }
}

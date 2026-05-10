# AGENTS.md

## Project Overview

Sherlock Holmes RAG Chatbot — a local, self-hosted Retrieval-Augmented Generation system over the complete Sherlock Holmes corpus. Built with opencode + Unsloth/Qwen3.6-35B-A3B-GGUF:Q4_K_XL.

## Tech Stack

- **PHP 8.4** CLI application
- **PostgreSQL 17** + pgvector for vector similarity search
- **llama.cpp** server (OpenAI-compatible API) for LLM inference
- **LLPhant** PHP library for document processing and embedding pipelines
- **Symfony Console** for CLI interface
- **Monolog** for logging

## Models

- **Chat/LLM**: `unsloth/Qwen3.6-35B-A3B-GGUF:Q4_K_XL` (served via llama.cpp at `192.168.1.20:8001`)
- **Embeddings**: OpenAI-compatible embedding API (768 dimensions, batch size 1)

## Key Architectural Decisions

- All external API calls (LLM/embeddings) are mocked via `OpenAI\Testing\ClientFake` in tests
- `ConsoleLogger` bridges Symfony `OutputInterface` with Monolog `HandlerInterface`
- PSR-3 `LoggerInterface` used throughout (not `Monolog\Logger` directly)
- `RagApplication` extends `Symfony\Component\Console\Application` (renamed from `Application.php` to avoid conflict)
- Progress bars use `Symfony\Component\Console\Helper\ProgressBar` with custom format
- Embedding pipeline pre-counts chunks before starting progress bar to avoid estimation errors

## Testing

- **371 tests**, 4472 assertions (100% pass rate)
- PHPUnit 13.1.8 with `displayDetailsOnTestsThatTriggerNotices=true`
- Base test class in `Tests\Support\TestCase` provides `ClientFake` setup and helper methods
- All external LLM/embedding calls mocked — no real API calls in tests
- Run: `docker exec pgvector-php bash -c "cd /app && vendor/bin/phpunit --no-coverage"`

## Common Patterns

### Creating a fake LLM response
```php
// In tests
$this->fakeChatResponse(['content' => 'Answer text']);
$this->fakeEmbeddingResponse([0.1, 0.2, ...]);
```

### Creating a ConsoleLogger
```php
$logger = ConsoleLogger::create($output); // returns Monolog\Logger
```

### Registering a command
```php
$app = new RagApplication(); // auto-registers EmbeddingGenerateCommand + ChatCommand
$app->run();
```

## Known Constraints

- Embedding batch size must be 1 (model limitation)
- Max input ~500 tokens per embedding request
- 128MB PHP memory limit in container
- Typed properties in `Chunk` entity are uninitialized by default (use reflection to check)
- LLPhant `Document` uses `sourceName` not `title`
- Chunk numbers are 0-indexed (not 1-indexed)
- PHPUnit 13 removed `withConsecutive()` — use callback approach instead

## Directory Structure

```
src/
  Chat/           — RAG pipeline, answer generation, context assessment
  Console/        — Symfony commands (RagApplication, ChatCommand, EmbeddingGenerateCommand)
  Documents/      — File reading, preprocessing, chunking
  EmbeddingGenerator/ — GenericEmbeddingGenerator
  Embeddings/     — EmbeddingService
  Entity/         — Doctrine Chunk entity
  Infrastructure/ — Database connection, LLM client
  Interfaces/     — Contract interfaces
  Logger/         — ConsoleLogger
  Pipeline/       — EmbeddingPipeline, EmbeddingStats

tests/
  Support/        — TestCase base class
  Unit/           — All unit tests (18 test files)
```

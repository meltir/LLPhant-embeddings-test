# Sherlock Holmes RAG Chatbot

A Docker Compose stack for building a Retrieval-Augmented Generation (RAG) system over the complete Sherlock Holmes story collection by Arthur Conan Doyle.

**Built with opencode + Unsloth/Qwen3.6-35B-A3B-GGUF (Q4_K_XL)** — This project was vibe-coded entirely using the opencode CLI tool powered by the Unsloth/Qwen3.6-35B-A3B-GGUF:Q4_K_XL quantized model.

## Architecture

```
┌──────────────┐     ┌──────────────────┐     ┌──────────────┐
│  PHP Scripts  │────▶│  PostgreSQL +    │     │  llama.cpp   │
│  (generate_   │     │  pgvector        │     │  Server      │
│   embeddings  │◀────│  (vector store)  │◀────│  (:8001)     │
│  , chat.php)  │     │                  │     │              │
└──────────────┘     └──────────────────┘     └──────────────┘
                             │
                     ┌───────▼───────┐
                     │    Adminer    │
                     │  (:8080)      │
                     └───────────────┘
```

### Services

| Service | Image | Purpose |
|---------|-------|---------|
| **db** | `pgvector/pgvector:pg17` | PostgreSQL 17 with pgvector extension for vector similarity search |
| **php** | `php:8.4-cli` | PHP CLI container running embedding generation and chat scripts |
| **adminer** | `adminer` | Web-based database admin interface |

## Goals

1. **Index the full Sherlock Holmes corpus** — All 12 stories from the text directory are chunked, embedded, and stored in a vector database.
2. **Enable RAG-based question answering** — A CLI chatbot takes natural language questions, searches the vector store for relevant passages, and generates answers using a local LLM.
3. **Keep everything local and self-hosted** — No external API calls. The LLM and vector store run entirely on your infrastructure.

## How It Works

### Embedding Generation (`generate_embeddings.php`)

1. Reads all `.txt` files from the `text/` directory using LLPhant's `FileDataReader`
2. Extracts the novel title from the first non-empty line of each file
3. Pre-processes content: removes Gutenberg footer, collapses excessive whitespace
4. Splits each story into chunks (~200 chars) using LLPhant's `DocumentSplitter` with sentence-level splitting and 10-word overlap
5. Generates embeddings via the local llama.cpp server (OpenAI-compatible API)
6. Stores each chunk in PostgreSQL with its embedding and associated novel title

### RAG Chatbot (`chat.php`)

For each user question, the bot runs a 3-step pipeline:

1. **Query Refinement** — The LLM condenses the question into a shorter, focused search query (excluding "Sherlock Holmes" since all documents are from that corpus).
2. **Vector Search** — The refined query is embedded and searched against pgvector using cosine similarity (L2 distance via `<->` operator). Starts with k=4 results.
3. **Context Evaluation** — The LLM assesses whether the retrieved chunks contain enough information to answer the question.
4. **Expand if Needed** — If context is insufficient, the search expands to k=12.
5. **Answer Generation** — The LLM generates a final answer using the retrieved passages as context.

## Constraints

### LLM Server

- **Endpoint**: `192.168.1.20:8001` (llama.cpp server exposing an OpenAI-compatible API)
- **Chat model**: `unsloth/Qwen3.6-35B-A3B-GGUF:Q4_K_XL`
- **Embedding model**: `unsloth/embeddinggemma-300m-GGUF:Q4_0` (768 dimensions)
- The embedding model must be loaded and available before running the embedding script
- The LLM server handles model loading on-demand; requests to an unloaded model will timeout

### Embedding Model Limitations

- **Batch size**: Must be 1 (the embeddinggemma-300m model on this server rejects batches larger than 1)
- **Max input size**: ~500 tokens per request (chunks are kept small to stay within this limit)
- **Dimension**: 768 (auto-detected at runtime)

### Chunking Strategy

- **Size**: ~200 characters per chunk (smaller than typical due to embedding model token limits)
- **Splitting**: By sentence (period delimiter)
- **Overlap**: 10 words between adjacent chunks
- **Result**: ~6,140 chunks across 12 stories (~500 per story)

### Memory

- The PHP container has a default 128MB memory limit
- Embeddings are generated one document at a time to stay within this constraint
- Batch processing is disabled for the embedding generator

## Setup

### Prerequisites

- Docker and Docker Compose
- Access to a llama.cpp server at `192.168.1.20:8001` (or update `LLM_URL` in `.env`)

### Quick Start

```bash
# 1. Configure environment variables
cp .env.example .env  # if you have a template, or edit .env directly

# 2. Start the stack
docker compose up -d

# 3. Generate embeddings (run once)
docker exec pgvector-php php generate_embeddings.php

# 4. Start the chatbot
docker exec -it pgvector-php php chat.php
```

### Stopping

```bash
docker compose down          # Stop containers, keep data
docker compose down -v       # Stop containers and delete data
```

## Project Structure

```
embeddings2/
├── .env                          # Environment variables
├── docker-compose.yml            # Service definitions
├── Dockerfile                    # PHP 8.4 CLI image
├── composer.json                 # PHP dependencies
├── generate_embeddings.php       # Embedding generation script
├── chat.php                      # RAG chatbot script
├── postgres/
│   └── schema.sql                # Database schema (chunks table)
├── src/
│   └── LlamaCppEmbeddingGenerator.php  # Custom embedding generator
└── text/                         # Sherlock Holmes stories (.txt)
    ├── bery.txt
    ├── blue.txt
    ├── bosc.txt
    ├── copp.txt
    ├── engr.txt
    ├── five.txt
    ├── iden.txt
    ├── nobl.txt
    ├── redh.txt
    ├── scan.txt
    ├── spec.txt
    └── twis.txt
```

## Database Schema

```sql
CREATE TABLE chunks (
    id SERIAL PRIMARY KEY,
    novel_title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    embedding vector(768),
    chunk_index INTEGER NOT NULL
);
```

## Adminer

Access the database admin interface at `http://localhost:8080`:

- **System**: Adminer
- **Server**: db
- **Username**: postgres
- **Password**: password (or from `.env`)
- **Database**: sherlock

## Tuning

These parameters can be adjusted in the PHP scripts:

| Parameter | Location | Default | Effect |
|-----------|----------|---------|--------|
| Chunk size | `generate_embeddings.php` | 200 chars | Smaller = more chunks, finer granularity |
| Chunk overlap | `generate_embeddings.php` | 10 words | More overlap = more context continuity |
| Initial k | `chat.php` | 4 | Number of chunks retrieved initially |
| Expanded k | `chat.php` | 12 | Number of chunks when context is insufficient |

## Troubleshooting

### Embedding model not loading

The llama.cpp server loads models on-demand. If embedding requests timeout, check model status:

```bash
curl http://192.168.1.20:8001/v1/models
```

### Table dimension mismatch

If you change the embedding model, the `chunks` table needs to be recreated with the correct dimension. The schema.sql should be updated and the database restarted.

### Memory exhaustion

If you see "Allowed memory size exhausted" errors, the chunk size or number of documents may be too large. Reduce chunk size in `generate_embeddings.php` or process fewer documents at a time.

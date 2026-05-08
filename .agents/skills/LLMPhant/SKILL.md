---
name: llphant
description: PHP Generative AI framework for chat, embeddings, vector stores, and RAG using llama.cpp (OpenAI-compatible API) with PostgreSQL, MariaDB, or Redis. Use when working with theodo-group/llphant.
---

# LLPhant Skill

You are an expert assistant for **LLPhant** (`theodo-group/llphant`), a PHP Generative AI framework. This project uses **llama.cpp** via an OpenAI-compatible API for both chat and embeddings, with **PostgreSQL**, **MariaDB**, or **Redis** for vector storage.

## Core Setup

- **PHP 8.1+**, namespace: `LLPhant\` (PSR-4 from `src/`)
- Install: `composer require theodo-group/llphant`
- Chat: `OpenAIChat` + `OpenAIConfig` (point `url` to llama.cpp server)
- Embeddings: `LmStudioEmbeddingGenerator` (works with any OpenAI-compatible endpoint)

## 1. Chat

```php
use LLPhant\Chat\OpenAIChat;
use LLPhant\OpenAIConfig;

$config = new OpenAIConfig();
$config->url = 'http://localhost:8080/v1';  // llama.cpp OpenAI-compatible endpoint
$config->apiKey = 'sk-not-needed';           // llama.cpp often ignores this
$config->model = 'your-model-name';
$chat = new OpenAIChat($config);

// Simple prompt
$response = $chat->generateText('Hello');

// System message
$chat->setSystemMessage('You are a helpful assistant.');
$response = $chat->generateText('Explain something');

// Conversation history
use LLPhant\Chat\Message;
$messages = [
    Message::system('You are a PHP expert.'),
    Message::user('What is array_map?'),
    Message::assistant('The function array_map applies a callback...'),
    Message::user('Show an example'),
];
$response = $chat->generateChat($messages);

// Streaming
$stream = $chat->generateStreamOfText('Write a poem');
foreach ($stream as $chunk) {
    echo $chunk;
}
```

## 2. Tool / Function Calling

```php
use LLPhant\Chat\FunctionBuilder;

class Mailer {
    public function sendMail(string $subject, string $body, string $email): void {
        // ... implementation
    }
}

$chat->addTool(FunctionBuilder::buildFunctionInfo(new Mailer(), 'sendMail'));
$chat->setSystemMessage('You are an AI that sends emails when you have enough info.');
$response = $chat->generateText('Send an email to user@example.com...');
// LLPhant auto-calls the tool and uses the result
```

Supported Parameter types: `string`, `int`, `float`, `bool`.

## 3. Embeddings & Vector Stores

### Embedding Generator

```php
use LLPhant\Embeddings\EmbeddingGenerator\LmStudio\LmStudioEmbeddingGenerator;

$generator = new LmStudioEmbeddingGenerator();
$generator->url = 'http://localhost:8080/v1';  // same llama.cpp endpoint
$embedding = $generator->embedText('Hello world');  // returns float[]
```

### Document Class

```php
use LLPhant\Embeddings\Document;

$doc = new Document();
$doc->content = 'The text content';
$doc->sourceType = 'file';
$doc->sourceName = 'document.txt';
```

### Document Ingestion Pipeline

```php
use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\EmbeddingGenerator\LmStudio\LmStudioEmbeddingGenerator;

// 1. Read documents (supports .txt, .pdf, .docx)
$reader = new FileDataReader('/path/to/documents');
$documents = $reader->getDocuments();

// 2. Split into chunks
$splitDocs = DocumentSplitter::splitDocuments($documents, chunkSize: 800, overlap: 100);

// 3. Generate embeddings
$generator = new LmStudioEmbeddingGenerator();
$generator->url = 'http://localhost:8080/v1';
$embeddedDocs = $generator->embedDocuments($splitDocs);

// 4. Store in vector store (see below)
```

### Vector Stores

#### PostgreSQL / MariaDB (Doctrine)

```php
use LLPhant\Embeddings\Doctrine\DoctrineVectorStore;
use LLPhant\Embeddings\Doctrine\DoctrineEmbeddingEntityBase;

// Entity must extend DoctrineEmbeddingEntityBase
#[Entity]
#[Table(name: 'my_documents')]
class MyEntity extends DoctrineEmbeddingEntityBase {
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $type;

    #[Column(type: VectorType::VECTOR, length: EMBEDDING_DIM)]  // set to your model's dimension
    public ?array $embedding;
}

$vectorStore = new DoctrineVectorStore($entityManager, MyEntity::class);
$vectorStore->addDocuments($embeddedDocs);

$results = $vectorStore->similaritySearch($queryEmbedding, k: 4);
```

> **PostgreSQL:** Run `CREATE EXTENSION IF NOT EXISTS vector;` first.
> **MariaDB:** Requires 11.7+.

#### Redis

```php
use Predis\Client;
use LLPhant\Embeddings\VectorStores\Redis\RedisVectorStore;

$redisClient = new Client(['scheme' => 'tcp', 'host' => 'localhost', 'port' => 6379]);
$vectorStore = new RedisVectorStore($redisClient, 'my_index');  // default: 'llphant'
$vectorStore->addDocuments($embeddedDocs);

$results = $vectorStore->similaritySearch($queryEmbedding, k: 4);
```

### Distance Calculations

LLPhant provides two distance metrics implementing `LLPhant\Embeddings\Distances\Distance`. They are used by vector stores and evaluators to measure similarity between embeddings.

#### Cosine Distance

Measures the angular difference between two vectors. Range: `0` (identical direction) to `2` (opposite directions). Normalized vectors produce values in `[0, 2]`; normalized similarity is often expressed as `1 - cosine_distance` = cosine similarity in `[-1, 1]`.

Best for: high-dimensional embeddings (like those from LLMs), where magnitude varies but direction encodes meaning.

```php
use LLPhant\Embeddings\Distances\CosineDistance;

$distance = new CosineDistance();
$similarity = $distance->measure($vectorA, $vectorB);  // lower = more similar
```

#### Euclidean Distance (L2)

Measures the straight-line distance between two points in vector space. Range: `[0, +inf)`. Zero means identical vectors.

Best for: lower-dimensional embeddings or when absolute magnitude differences matter.

```php
use LLPhant\Embeddings\Distances\EuclideanDistanceL2;

$distance = new EuclideanDistanceL2();
$similarity = $distance->measure($vectorA, $vectorB);  // lower = more similar
```

#### Using Custom Distance with Vector Stores

Both `MemoryVectorStore` and `FileSystemVectorStore` accept a distance metric via constructor:

```php
use LLPhant\Embeddings\VectorStores\Memory\MemoryVectorStore;
use LLPhant\Embeddings\Distances\CosineDistance;

$vectorStore = new MemoryVectorStore(new CosineDistance());
$vectorStore->addDocuments($embeddedDocs);

$results = $vectorStore->similaritySearch($queryEmbedding, k: 4);
```

> **Default:** `MemoryVectorStore` and `FileSystemVectorStore` use `EuclideanDistanceL2` by default.
> **Doctrine/Redis vector stores** use database-native distance functions (L2 / `VEC_DISTANCE_EUCLIDEAN`) and do not accept a custom distance parameter.

## 4. RAG / Question Answering

```php
use LLPhant\Query\SemanticSearch\QuestionAnswering;
use LLPhant\Embeddings\EmbeddingGenerator\LmStudio\LmStudioEmbeddingGenerator;
use LLPhant\Chat\OpenAIChat;

$qa = new QuestionAnswering(
    $vectorStore,
    new LmStudioEmbeddingGenerator(),
    new OpenAIChat()
);

$answer = $qa->answerQuestion('What is in my documents?');
```

### Chat Session (Memory)

```php
use LLPhant\Query\SemanticSearch\ChatSession;

$qa = new QuestionAnswering(
    $vectorStore,
    $generator,
    $chat,
    session: new ChatSession()
);

$qa->answerQuestion('Who was the first Roman Emperor?');
$qa->answerQuestion('And the third?');  // Context preserved
```

### Reranking

```php
use LLPhant\Query\SemanticSearch\LLMReranker;

$reranker = new LLMReranker($chat, nrOfOutputDocuments: 3);
$qa = new QuestionAnswering(
    $vectorStore,
    $generator,
    $chat,
    retrievedDocumentsTransformer: $reranker
);
```

### Custom System Message

```php
$qa->systemMessageTemplate = 'You are a helpful assistant. Answer conversationally.\n\n{context}.';
```

## 5. Evaluation & Guardrails

### Evaluation Strategies

```php
use LLPhant\Evaluation\StringComparison\StringComparisonEvaluator;
use LLPhant\Chat\Message;

$evaluator = new StringComparisonEvaluator();
$results = $evaluator->evaluateMessages(
    [Message::user('generated answer')],
    ['expected reference answer']
);
$scores = $results->getResults();
// Returns: ROUGE, BLEU, METEOR metrics
```

### Criteria Evaluator (LLM-as-Judge)

```php
use LLPhant\Evaluation\Criteria\CriteriaEvaluator;
use LLPhant\Evaluation\Criteria\CriteriaEvaluatorPromptBuilder;

$builder = (new CriteriaEvaluatorPromptBuilder())
    ->addCorrectness()
    ->addHelpfulness()
    ->addRelevance();

$evaluator = new CriteriaEvaluator();
$results = $evaluator->evaluateMessages([Message::user('answer')], ['question']);
$scores = $results->getResults(); // 1-5 scores per criterion
```

### Embedding Distance Evaluator

```php
use LLPhant\Evaluation\EmbeddingDistance\EmbeddingDistanceEvaluator;
use LLPhant\Embeddings\Distances\EuclideanDistanceL2;

$evaluator = new EmbeddingDistanceEvaluator(
    new LmStudioEmbeddingGenerator(),
    new EuclideanDistanceL2()
);
$results = $evaluator->evaluateMessages(
    [Message::user('candidate text')],
    ['reference text']
);
```

### Output Validators

```php
use LLPhant\Evaluation\Output\JSONFormatEvaluator;
use LLPhant\Evaluation\Output\WordLimitEvaluator;
use LLPhant\Evaluation\Output\NoFallbackAnswerEvaluator;

// JSON format validation
$jsonEval = new JSONFormatEvaluator();
$results = $jsonEval->evaluateText('{"key":"value"}');

// Word limit
$wordEval = (new WordLimitEvaluator())->setWordLimit(50);
$results = $wordEval->evaluateText($text);

// Detect fallback answers
$fallbackEval = new NoFallbackAnswerEvaluator();
$results = $fallbackEval->evaluateText("I'm sorry, I cannot help...");
```

### Guardrails (Auto-Retry / Block)

```php
use LLPhant\Evaluation\Guardrails\Guardrails;
use LLPhant\Evaluation\Guardrails\GuardrailStrategy;
use LLPhant\Evaluation\Output\JSONFormatEvaluator;

$llm = new OpenAIChat($config);
$guardrails = new Guardrails(llm: $llm);
$guardrails->addStrategy(new JSONFormatEvaluator(), GuardrailStrategy::STRATEGY_RETRY);
$guardrails->addStrategy(
    new NoFallbackAnswerEvaluator(),
    GuardrailStrategy::STRATEGY_BLOCK,
    defaultMessage: "I'm unable to answer your question right now."
);

$response = $guardrails->generateText('Generate JSON output');
```

## Key Namespace Reference

```
LLPhant\Chat\OpenAIChat                    - Chat (works with llama.cpp via OpenAIConfig)
LLPhant\Chat\Message::user() / ::system() / ::assistant()
LLPhant\Chat\FunctionBuilder::buildFunctionInfo()
LLPhant\Chat\FunctionInfo, Parameter

LLPhant\Embeddings\Document
LLPhant\Embeddings\EmbeddingGenerator\LmStudio\LmStudioEmbeddingGenerator
LLPhant\Embeddings\DataReader\FileDataReader
LLPhant\Embeddings\DocumentSplitter\DocumentSplitter
LLPhant\Embeddings\VectorStores\Doctrine\DoctrineVectorStore
LLPhant\Embeddings\VectorStores\Redis\RedisVectorStore

LLPhant\Query\SemanticSearch\QuestionAnswering
LLPhant\Query\SemanticSearch\ChatSession
LLPhant\Query\SemanticSearch\LLMReranker

LLPhant\Evaluation\StringComparison\StringComparisonEvaluator
LLPhant\Evaluation\Criteria\CriteriaEvaluator
LLPhant\Evaluation\EmbeddingDistance\EmbeddingDistanceEvaluator
LLPhant\Evaluation\Output\JSONFormatEvaluator
LLPhant\Evaluation\Output\WordLimitEvaluator
LLPhant\Evaluation\Output\NoFallbackAnswerEvaluator
LLPhant\Evaluation\Guardrails\Guardrails
```

## Common Pitfalls

1. **Embedding length mismatch:** The `VectorType::VECTOR` `length` in your Doctrine entity must match your llama.cpp model's embedding dimension.
2. **PostgreSQL:** Must run `CREATE EXTENSION IF NOT EXISTS vector;` before using Doctrine vector store.
3. **Streaming:** `generateStreamOfText()` returns a `StreamInterface` that must be iterated.
4. **Timeout:** Set `$config->timeout = 30.0;` on `OpenAIConfig` if requests time out.

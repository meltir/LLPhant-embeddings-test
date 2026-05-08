<?php

declare(strict_types=1);

namespace Tests\Unit\Interfaces;

use App\Interfaces\IDocumentReader;
use App\Interfaces\IDocumentPreprocessor;
use App\Interfaces\IDocumentChunker;
use App\Interfaces\IVectorSearch;
use App\Interfaces\IAnswerGenerator;
use App\Interfaces\IContextAssessor;
use App\Interfaces\IQueryRefiner;
use LLPhant\Embeddings\Document;
use Tests\Support\TestCase;

class DocumentReaderInterfaceTest extends TestCase
{
    public function test_idocument_reader_interface_exists(): void
    {
        $this->assertTrue(interface_exists(IDocumentReader::class));
    }

    public function test_idocument_reader_has_read_method(): void
    {
        $reflection = new \ReflectionClass(IDocumentReader::class);
        $this->assertTrue($reflection->hasMethod('read'));

        $method = $reflection->getMethod('read');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isAbstract());

        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('directory', $params[0]->getName());
        $this->assertEquals('string', (string) $params[0]->getType());
    }

    public function test_idocument_reader_read_returns_document_array(): void
    {
        $reflection = new \ReflectionClass(IDocumentReader::class);
        $method = $reflection->getMethod('read');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', (string) $returnType);
    }
}

class DocumentPreprocessorInterfaceTest extends TestCase
{
    public function test_idocument_preprocessor_interface_exists(): void
    {
        $this->assertTrue(interface_exists(IDocumentPreprocessor::class));
    }

    public function test_idocument_preprocessor_has_preprocess_method(): void
    {
        $reflection = new \ReflectionClass(IDocumentPreprocessor::class);
        $this->assertTrue($reflection->hasMethod('preprocess'));

        $method = $reflection->getMethod('preprocess');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isAbstract());

        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertEquals('document', $params[0]->getName());
        $this->assertEquals('title', $params[1]->getName());
    }

    public function test_idocument_preprocessor_preprocess_returns_document(): void
    {
        $reflection = new \ReflectionClass(IDocumentPreprocessor::class);
        $method = $reflection->getMethod('preprocess');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals(Document::class, (string) $returnType);
    }
}

class DocumentChunkerInterfaceTest extends TestCase
{
    public function test_idocument_chunker_interface_exists(): void
    {
        $this->assertTrue(interface_exists(IDocumentChunker::class));
    }

    public function test_idocument_chunker_has_chunk_method(): void
    {
        $reflection = new \ReflectionClass(IDocumentChunker::class);
        $this->assertTrue($reflection->hasMethod('chunk'));

        $method = $reflection->getMethod('chunk');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isAbstract());

        $params = $method->getParameters();
        $this->assertCount(4, $params);
        $this->assertEquals('document', $params[0]->getName());
        $this->assertEquals('maxLength', $params[1]->getName());
        $this->assertEquals('separator', $params[2]->getName());
        $this->assertEquals('wordOverlap', $params[3]->getName());
    }

    public function test_idocument_chunker_chunk_returns_document_array(): void
    {
        $reflection = new \ReflectionClass(IDocumentChunker::class);
        $method = $reflection->getMethod('chunk');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', (string) $returnType);
    }
}

class VectorSearchInterfaceTest extends TestCase
{
    public function test_ivector_search_interface_exists(): void
    {
        $this->assertTrue(interface_exists(IVectorSearch::class));
    }

    public function test_ivector_search_has_search_method(): void
    {
        $reflection = new \ReflectionClass(IVectorSearch::class);
        $this->assertTrue($reflection->hasMethod('search'));

        $method = $reflection->getMethod('search');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isAbstract());

        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertEquals('embedding', $params[0]->getName());
        $this->assertEquals('k', $params[1]->getName());
    }

    public function test_ivector_search_search_returns_document_array(): void
    {
        $reflection = new \ReflectionClass(IVectorSearch::class);
        $method = $reflection->getMethod('search');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', (string) $returnType);
    }

    public function test_ivector_search_search_default_k_is_four(): void
    {
        $reflection = new \ReflectionClass(IVectorSearch::class);
        $method = $reflection->getMethod('search');

        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertTrue($params[1]->isOptional());
        $this->assertEquals(4, $params[1]->getDefaultValue());
    }
}

class AnswerGeneratorInterfaceTest extends TestCase
{
    public function test_ianswer_generator_interface_exists(): void
    {
        $this->assertTrue(interface_exists(IAnswerGenerator::class));
    }

    public function test_ianswer_generator_has_generate_method(): void
    {
        $reflection = new \ReflectionClass(IAnswerGenerator::class);
        $this->assertTrue($reflection->hasMethod('generate'));

        $method = $reflection->getMethod('generate');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isAbstract());

        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertEquals('question', $params[0]->getName());
        $this->assertEquals('context', $params[1]->getName());
    }

    public function test_ianswer_generator_generate_returns_string(): void
    {
        $reflection = new \ReflectionClass(IAnswerGenerator::class);
        $method = $reflection->getMethod('generate');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', (string) $returnType);
    }
}

class ContextAssessorInterfaceTest extends TestCase
{
    public function test_icontext_assessor_interface_exists(): void
    {
        $this->assertTrue(interface_exists(IContextAssessor::class));
    }

    public function test_icontext_assessor_has_assess_method(): void
    {
        $reflection = new \ReflectionClass(IContextAssessor::class);
        $this->assertTrue($reflection->hasMethod('assess'));

        $method = $reflection->getMethod('assess');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isAbstract());

        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertEquals('question', $params[0]->getName());
        $this->assertEquals('context', $params[1]->getName());
    }

    public function test_icontext_assessor_assess_returns_string(): void
    {
        $reflection = new \ReflectionClass(IContextAssessor::class);
        $method = $reflection->getMethod('assess');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', (string) $returnType);
    }
}

class QueryRefinerInterfaceTest extends TestCase
{
    public function test_iquery_refiner_interface_exists(): void
    {
        $this->assertTrue(interface_exists(IQueryRefiner::class));
    }

    public function test_iquery_refiner_has_refine_method(): void
    {
        $reflection = new \ReflectionClass(IQueryRefiner::class);
        $this->assertTrue($reflection->hasMethod('refine'));

        $method = $reflection->getMethod('refine');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isAbstract());

        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('question', $params[0]->getName());
    }

    public function test_iquery_refiner_refine_returns_string(): void
    {
        $reflection = new \ReflectionClass(IQueryRefiner::class);
        $method = $reflection->getMethod('refine');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', (string) $returnType);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Documents;

use App\Documents\TextDocumentPreprocessor;
use LLPhant\Embeddings\Document;
use Tests\Support\TestCase;

class TextDocumentPreprocessorTest extends TestCase
{
    private TextDocumentPreprocessor $preprocessor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preprocessor = new TextDocumentPreprocessor();
    }

    public function testPreprocessSetsTitle(): void
    {
        $doc = new Document();
        $doc->content = 'Some content here.';
        $title = 'The Adventure of the Speckled Band';

        $result = $this->preprocessor->preprocess($doc, $title);

        $this->assertEquals($title, $result->sourceName);
    }

    public function testPreprocessRemovesFooterPattern(): void
    {
        $content = "This is the main content.\n\n----------\n\n"
            . "This text comes from the collection of Sherlock Holmes stories.";
        $doc = new Document();
        $doc->content = $content;

        $result = $this->preprocessor->preprocess($doc, 'Test Story');

        $this->assertStringNotContainsString('----------', $result->content);
        $this->assertStringNotContainsString('This text comes from the collection', $result->content);
        $this->assertStringContainsString('This is the main content.', $result->content);
    }

    public function testPreprocessCollapsesTripleNewlines(): void
    {
        $content = "Line one.\n\n\n\n\nLine two.\n\n\nLine three.";
        $doc = new Document();
        $doc->content = $content;

        $result = $this->preprocessor->preprocess($doc, 'Test Story');

        $this->assertStringNotContainsString("\n\n\n", $result->content);
        $this->assertStringContainsString("Line one.\n\nLine two.\n\nLine three.", $result->content);
    }

    public function testPreprocessTrimsWhitespace(): void
    {
        $content = "   Content with whitespace   \n\n";
        $doc = new Document();
        $doc->content = $content;

        $result = $this->preprocessor->preprocess($doc, 'Test Story');

        $this->assertEquals('Content with whitespace', $result->content);
    }

    public function testPreprocessHandlesEmptyContent(): void
    {
        $doc = new Document();
        $doc->content = '';

        $result = $this->preprocessor->preprocess($doc, 'Empty Story');

        $this->assertEquals('', $result->content);
        $this->assertEquals('Empty Story', $result->sourceName);
    }

    public function testPreprocessHandlesWhitespaceOnlyContent(): void
    {
        $doc = new Document();
        $doc->content = "   \n\n   \n";

        $result = $this->preprocessor->preprocess($doc, 'Whitespace Story');

        $this->assertEquals('', trim($result->content));
    }

    public function testPreprocessHandlesContentWithoutFooter(): void
    {
        $content = "Just regular content without any footer.";
        $doc = new Document();
        $doc->content = $content;

        $result = $this->preprocessor->preprocess($doc, 'Simple Story');

        $this->assertEquals($content, $result->content);
    }

    public function testPreprocessPreservesSingleNewlines(): void
    {
        $content = "Line one.\nLine two.\nLine three.";
        $doc = new Document();
        $doc->content = $content;

        $result = $this->preprocessor->preprocess($doc, 'Multi-line Story');

        $this->assertEquals($content, $result->content);
    }

    public function testPreprocessPreservesDoubleNewlines(): void
    {
        $content = "Paragraph one.\n\nParagraph two.";
        $doc = new Document();
        $doc->content = $content;

        $result = $this->preprocessor->preprocess($doc, 'Paragraph Story');

        $this->assertEquals($content, $result->content);
    }

    public function testPreprocessHandlesComplexFooter(): void
    {
        $content = "The End.\n\n----------\n\nAnd other tales.\n\n"
            . "This text comes from the collection\nof wonderful stories by Arthur Conan Doyle.";
        $doc = new Document();
        $doc->content = $content;

        $result = $this->preprocessor->preprocess($doc, 'Complex Story');

        $this->assertStringNotContainsString('This text comes from the collection', $result->content);
        $this->assertStringContainsString('The End.', $result->content);
    }

    public function testPreprocessHandlesContentWithOnlyDashes(): void
    {
        $content = "Content before.\n----------\nThis text comes from the collection.";
        $doc = new Document();
        $doc->content = $content;

        $result = $this->preprocessor->preprocess($doc, 'Dash Story');

        $this->assertStringNotContainsString('----------', $result->content);
        $this->assertStringNotContainsString('This text comes from the collection', $result->content);
        $this->assertStringContainsString('Content before.', $result->content);
    }

    public function testPreprocessReturnsSameDocumentInstance(): void
    {
        $doc = new Document();
        $doc->content = 'Test content.';

        $result = $this->preprocessor->preprocess($doc, 'Test');

        $this->assertSame($doc, $result);
    }

    public function testPreprocessHandlesUnicodeContent(): void
    {
        $content = "Café résumé naïve. \n\n----------\n\nThis text comes from the collection.";
        $doc = new Document();
        $doc->content = $content;

        $result = $this->preprocessor->preprocess($doc, 'Unicode Story');

        $this->assertStringContainsString('Café résumé naïve', $result->content);
        $this->assertStringNotContainsString('This text comes from the collection', $result->content);
    }

    public function testPreprocessHandlesSpecialCharacters(): void
    {
        $content = "Special chars: @#\$%^&*()!\n\n----------\n\nThis text comes from the collection.";
        $doc = new Document();
        $doc->content = $content;

        $result = $this->preprocessor->preprocess($doc, 'Special Story');

        $this->assertStringContainsString('Special chars:', $result->content);
        $this->assertStringNotContainsString('This text comes from the collection', $result->content);
    }

    public function testPreprocessHandlesLongContent(): void
    {
        $longContent = str_repeat('This is a test sentence. ', 100);
        $doc = new Document();
        $doc->content = $longContent;

        $result = $this->preprocessor->preprocess($doc, 'Long Story');

        $this->assertGreaterThan(2000, strlen($result->content));
        $this->assertEquals('Long Story', $result->sourceName);
    }

    public function testPreprocessHandlesContentWithMixedWhitespace(): void
    {
        $content = "   Content with tabs\t\tand spaces   \n\n   \n\nMore content   ";
        $doc = new Document();
        $doc->content = $content;

        $result = $this->preprocessor->preprocess($doc, 'Mixed Whitespace');

        $this->assertStringContainsString('Content with tabs', $result->content);
        $this->assertStringContainsString('More content', $result->content);
        $this->assertEquals('Mixed Whitespace', $result->sourceName);
    }

    public function testPreprocessHandlesTitleWithSpecialChars(): void
    {
        $doc = new Document();
        $doc->content = 'Some content.';
        $title = 'The Adventure #1: A Special Case!';

        $result = $this->preprocessor->preprocess($doc, $title);

        $this->assertEquals($title, $result->sourceName);
    }

    public function testPreprocessHandlesEmptyTitle(): void
    {
        $doc = new Document();
        $doc->content = 'Some content.';

        $result = $this->preprocessor->preprocess($doc, '');

        $this->assertEquals('', $result->sourceName);
    }
}

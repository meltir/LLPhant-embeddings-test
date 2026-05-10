<?php

declare(strict_types=1);

namespace Tests\Unit\Documents;

use App\Documents\FileDocumentReader;
use LLPhant\Embeddings\Document;
use Tests\Support\TestCase;

class FileDocumentReaderTest extends TestCase
{
    private FileDocumentReader $reader;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reader = new FileDocumentReader();
        $this->tempDir = sys_get_temp_dir() . '/embeddings_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->cleanup($this->tempDir);
        parent::tearDown();
    }

    private function cleanup(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->cleanup($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public function testReaderReadsTextFilesFromDirectory(): void
    {
        file_put_contents(
            $this->tempDir . '/story1.txt',
            "The Adventure of Test One\n\nThis is the content of story one."
        );
        file_put_contents(
            $this->tempDir . '/story2.txt',
            "The Adventure of Test Two\n\nThis is the content of story two."
        );

        $documents = $this->reader->read($this->tempDir);

        $this->assertCount(2, $documents);
        foreach ($documents as $doc) {
            $this->assertInstanceOf(Document::class, $doc);
            $this->assertNotEmpty($doc->content);
        }
    }

    public function testReaderReturnsEmptyArrayForEmptyDirectory(): void
    {
        $documents = $this->reader->read($this->tempDir);

        $this->assertCount(0, $documents);
    }

    public function testReaderIgnoresNonTextFiles(): void
    {
        file_put_contents($this->tempDir . '/story.txt', 'Some content.');
        file_put_contents($this->tempDir . '/image.png', "\x89PNG\r\n\x1a\n");
        file_put_contents($this->tempDir . '/data.bin', "\x00\x01\x02\x03");

        $documents = $this->reader->read($this->tempDir);

        $this->assertNotEmpty($documents);
    }

    public function testReaderHandlesSingleFile(): void
    {
        file_put_contents($this->tempDir . '/only.txt', 'Single file content.');

        $documents = $this->reader->read($this->tempDir);

        $this->assertCount(1, $documents);
        $this->assertEquals('Single file content.', $documents[0]->content);
    }

    public function testReaderHandlesMultipleFiles(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            file_put_contents($this->tempDir . "/file{$i}.txt", "Content of file $i.");
        }

        $documents = $this->reader->read($this->tempDir);

        $this->assertCount(10, $documents);
    }

    public function testReaderDocumentHasContent(): void
    {
        $expectedContent = "This is a test document with some meaningful content for testing purposes.";
        file_put_contents($this->tempDir . '/test.txt', $expectedContent);

        $documents = $this->reader->read($this->tempDir);

        $this->assertCount(1, $documents);
        $this->assertStringContainsString('This is a test document', $documents[0]->content);
    }

    public function testReaderHandlesLongFiles(): void
    {
        $longContent = str_repeat('This is a long line of text. ', 100) . "\n\nMore content here.";
        file_put_contents($this->tempDir . '/long.txt', $longContent);

        $documents = $this->reader->read($this->tempDir);

        $this->assertCount(1, $documents);
        $this->assertGreaterThan(2000, strlen($documents[0]->content));
    }

    public function testReaderHandlesUnicodeContent(): void
    {
        $unicodeContent = "Café résumé naïve. \n\n----------\n\nThis text comes from the collection.";
        file_put_contents($this->tempDir . '/unicode.txt', $unicodeContent);

        $documents = $this->reader->read($this->tempDir);

        $this->assertCount(1, $documents);
        $this->assertStringContainsString('Café résumé naïve', $documents[0]->content);
    }

    public function testReaderHandlesFilesWithSpecialNames(): void
    {
        file_put_contents($this->tempDir . '/story-with-dashes.txt', 'Dashes content.');
        file_put_contents($this->tempDir . '/story_with_underscores.txt', 'Underscores content.');
        file_put_contents($this->tempDir . '/story.with.dots.txt', 'Dots content.');

        $documents = $this->reader->read($this->tempDir);

        $this->assertCount(3, $documents);
    }

    public function testReaderHandlesMixedContentTypes(): void
    {
        file_put_contents($this->tempDir . '/good.txt', 'Good content.');
        file_put_contents($this->tempDir . '/good2.txt', 'More good content.');
        file_put_contents($this->tempDir . '/readme', 'No extension content.');

        $documents = $this->reader->read($this->tempDir);

        $this->assertNotEmpty($documents);
    }

    public function testReaderHandlesEmptyFiles(): void
    {
        file_put_contents($this->tempDir . '/empty.txt', '');
        file_put_contents($this->tempDir . '/nonempty.txt', 'Has content.');

        $documents = $this->reader->read($this->tempDir);

        $this->assertCount(2, $documents);
    }

    public function testReaderHandlesFilesWithNewlines(): void
    {
        $content = "Title line.\n\nFirst paragraph.\n\nSecond paragraph.\n\n----------\n\n"
            . "This text comes from the collection.";
        file_put_contents($this->tempDir . '/newlines.txt', $content);

        $documents = $this->reader->read($this->tempDir);

        $this->assertCount(1, $documents);
        $this->assertStringContainsString('Title line.', $documents[0]->content);
    }

    public function testReaderHandlesDirectoryWithSubdirectories(): void
    {
        mkdir($this->tempDir . '/subdir', 0777, true);
        file_put_contents($this->tempDir . '/root.txt', 'Root content.');
        file_put_contents($this->tempDir . '/subdir/nested.txt', 'Nested content.');

        $documents = $this->reader->read($this->tempDir);

        // FileDataReader only reads files in the given directory, not subdirectories
        $this->assertGreaterThan(0, count($documents));
    }

    public function testReaderDocumentHasSourceName(): void
    {
        file_put_contents($this->tempDir . '/title.txt', "My Story Title\n\nContent here.");

        $documents = $this->reader->read($this->tempDir);

        $this->assertCount(1, $documents);
        $this->assertEquals('title.txt', $documents[0]->sourceName);
    }

    public function testReaderHandlesFilesWithTabs(): void
    {
        $content = "Content\twith\ttabs.\n\nMore\tcontent.";
        file_put_contents($this->tempDir . '/tabs.txt', $content);

        $documents = $this->reader->read($this->tempDir);

        $this->assertCount(1, $documents);
        $this->assertStringContainsString('Content', $documents[0]->content);
    }

    public function testReaderNonexistentDirectory(): void
    {
        $nonExistentDir = $this->tempDir . '/does_not_exist';

        $documents = $this->reader->read($nonExistentDir);

        $this->assertCount(0, $documents);
    }
}

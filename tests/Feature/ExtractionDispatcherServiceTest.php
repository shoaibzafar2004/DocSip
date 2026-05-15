<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Services\ExtractionDispatcherService;
use App\Services\OcrExtractionService;
use App\Services\PdfExtractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtractionDispatcherServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeDispatcher(): ExtractionDispatcherService
    {
        return app(ExtractionDispatcherService::class);
    }

    public function test_pdf_with_sufficient_text_uses_pdftotext(): void
    {
        $document = Document::factory()->create(['mime_type' => 'application/pdf']);

        $this->mock(PdfExtractionService::class)
            ->shouldReceive('extract')
            ->andReturn(str_repeat('word ', 30));

        $this->mock(OcrExtractionService::class)
            ->shouldNotReceive('extract');

        $result = $this->makeDispatcher()->extract($document, '/fake/path.pdf');

        $this->assertSame('pdftotext', $result['extraction_method']);
        $this->assertNull($result['confidence']);
    }

    public function test_pdf_with_short_text_falls_back_to_ocr(): void
    {
        $document = Document::factory()->create(['mime_type' => 'application/pdf']);

        $this->mock(PdfExtractionService::class)
            ->shouldReceive('extract')
            ->andReturn('too short');

        $this->mock(OcrExtractionService::class)
            ->shouldReceive('extract')
            ->andReturn(['text' => str_repeat('word ', 30), 'confidence' => 88.0]);

        $result = $this->makeDispatcher()->extract($document, '/fake/path.pdf');

        $this->assertSame('tesseract', $result['extraction_method']);
        $this->assertSame(88.0, $result['confidence']);
    }

    public function test_pdf_pdftotext_exception_falls_back_to_ocr(): void
    {
        $document = Document::factory()->create(['mime_type' => 'application/pdf']);

        $this->mock(PdfExtractionService::class)
            ->shouldReceive('extract')
            ->andThrow(new \Exception('pdftotext not found'));

        $this->mock(OcrExtractionService::class)
            ->shouldReceive('extract')
            ->andReturn(['text' => str_repeat('word ', 30), 'confidence' => 72.0]);

        $result = $this->makeDispatcher()->extract($document, '/fake/path.pdf');

        $this->assertSame('tesseract', $result['extraction_method']);
    }

    public function test_image_skips_pdftotext_and_uses_ocr_directly(): void
    {
        $document = Document::factory()->create(['mime_type' => 'image/jpeg']);

        $this->mock(PdfExtractionService::class)
            ->shouldNotReceive('extract');

        $this->mock(OcrExtractionService::class)
            ->shouldReceive('extract')
            ->andReturn(['text' => str_repeat('word ', 30), 'confidence' => 91.0]);

        $result = $this->makeDispatcher()->extract($document, '/fake/image.jpg');

        $this->assertSame('tesseract', $result['extraction_method']);
        $this->assertSame(91.0, $result['confidence']);
    }

    public function test_plain_text_returns_file_contents(): void
    {
        $document = Document::factory()->create(['mime_type' => 'text/plain']);
        $path = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($path, 'Hello plain text file');

        $this->mock(PdfExtractionService::class)->shouldNotReceive('extract');
        $this->mock(OcrExtractionService::class)->shouldNotReceive('extract');

        $result = $this->makeDispatcher()->extract($document, $path);

        unlink($path);

        $this->assertSame('Hello plain text file', $result['text']);
        $this->assertSame('plaintext', $result['extraction_method']);
        $this->assertNull($result['confidence']);
    }

    public function test_unsupported_mime_type_throws_exception(): void
    {
        $document = Document::factory()->create(['mime_type' => 'application/zip']);

        $this->expectException(\RuntimeException::class);

        $this->makeDispatcher()->extract($document, '/fake/file.zip');
    }
}

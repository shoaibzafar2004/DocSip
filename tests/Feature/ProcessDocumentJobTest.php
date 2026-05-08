<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentChunkStorageService;
use App\Services\PdfExtractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessDocumentJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_is_dispatched_when_document_is_uploaded(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('report.pdf', 512, 'application/pdf');

        $this->actingAs($user)->postJson('/documents', ['file' => $file]);

        Queue::assertPushed(ProcessDocumentJob::class, fn ($job) => $job->document->user_id === $user->id);
    }

    public function test_job_sets_status_to_ready_on_successful_processing(): void
    {
        $document = Document::factory()->uploaded()->create();

        $this->mock(PdfExtractionService::class)
            ->shouldReceive('extract')
            ->andReturn(str_repeat('word ', 30));

        $this->mock(DocumentChunkStorageService::class)
            ->shouldReceive('store');

        app()->call([new ProcessDocumentJob($document), 'handle']);

        $this->assertSame('ready', $document->fresh()->status);
    }

    public function test_job_clears_status_message_on_success(): void
    {
        $document = Document::factory()->uploaded()->create([
            'status_message' => 'Retrying... (attempt 2 of 3)',
        ]);

        $this->mock(PdfExtractionService::class)
            ->shouldReceive('extract')
            ->andReturn(str_repeat('word ', 30));

        $this->mock(DocumentChunkStorageService::class)
            ->shouldReceive('store');

        app()->call([new ProcessDocumentJob($document), 'handle']);

        $this->assertNull($document->fresh()->status_message);
    }

    public function test_job_sets_status_to_failed_when_extraction_throws(): void
    {
        $document = Document::factory()->uploaded()->create();

        $this->mock(PdfExtractionService::class)
            ->shouldReceive('extract')
            ->andThrow(new \Exception('pdftotext not found'));

        $this->mock(DocumentChunkStorageService::class)
            ->shouldNotReceive('store');

        app()->call([new ProcessDocumentJob($document), 'handle']);

        $this->assertSame('failed', $document->fresh()->status);
    }

    public function test_job_sets_status_message_when_extraction_fails(): void
    {
        $document = Document::factory()->uploaded()->create();

        $this->mock(PdfExtractionService::class)
            ->shouldReceive('extract')
            ->andThrow(new \Exception('pdftotext not found'));

        $this->mock(DocumentChunkStorageService::class);

        app()->call([new ProcessDocumentJob($document), 'handle']);

        $this->assertNotNull($document->fresh()->status_message);
    }

    public function test_job_sets_status_to_failed_when_content_is_too_short(): void
    {
        $document = Document::factory()->uploaded()->create();

        $this->mock(PdfExtractionService::class)
            ->shouldReceive('extract')
            ->andReturn('too short');

        $this->mock(DocumentChunkStorageService::class)
            ->shouldNotReceive('store');

        app()->call([new ProcessDocumentJob($document), 'handle']);

        $this->assertSame('failed', $document->fresh()->status);
    }

    public function test_job_sets_status_message_when_content_is_too_short(): void
    {
        $document = Document::factory()->uploaded()->create();

        $this->mock(PdfExtractionService::class)
            ->shouldReceive('extract')
            ->andReturn('too short');

        $this->mock(DocumentChunkStorageService::class);

        app()->call([new ProcessDocumentJob($document), 'handle']);

        $this->assertNotNull($document->fresh()->status_message);
    }

    public function test_job_propagates_exception_when_chunk_storage_throws(): void
    {
        $document = Document::factory()->uploaded()->create();

        $this->mock(PdfExtractionService::class)
            ->shouldReceive('extract')
            ->andReturn(str_repeat('word ', 30));

        $this->mock(DocumentChunkStorageService::class)
            ->shouldReceive('store')
            ->andThrow(new \Exception('Rate limit exceeded'));

        $this->expectException(\Exception::class);

        app()->call([new ProcessDocumentJob($document), 'handle']);
    }

    public function test_failed_method_sets_status_to_failed_with_message(): void
    {
        $document = Document::factory()->processing()->create();
        $job = new ProcessDocumentJob($document);

        $job->failed(new \Exception('Rate limit exceeded'));

        $this->assertSame('failed', $document->fresh()->status);
        $this->assertNotNull($document->fresh()->status_message);
    }

    public function test_failed_method_does_not_overwrite_existing_failure_message(): void
    {
        $document = Document::factory()->failed()->create([
            'status_message' => 'Could not read this PDF. Remove it and try a different file.',
        ]);
        $job = new ProcessDocumentJob($document);

        $job->failed(new \Exception('Some error'));

        $this->assertSame(
            'Could not read this PDF. Remove it and try a different file.',
            $document->fresh()->status_message,
        );
    }

    public function test_job_logs_error_when_extraction_fails(): void
    {
        Log::spy();

        $document = Document::factory()->uploaded()->create();

        $this->mock(PdfExtractionService::class)
            ->shouldReceive('extract')
            ->andThrow(new \Exception('pdftotext not found'));

        $this->mock(DocumentChunkStorageService::class);

        app()->call([new ProcessDocumentJob($document), 'handle']);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Text extraction failed', \Mockery::subset(['document_id' => $document->id]));
    }

    public function test_job_logs_error_when_content_is_too_short(): void
    {
        Log::spy();

        $document = Document::factory()->uploaded()->create();

        $this->mock(PdfExtractionService::class)
            ->shouldReceive('extract')
            ->andReturn('short');

        $this->mock(DocumentChunkStorageService::class);

        app()->call([new ProcessDocumentJob($document), 'handle']);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Processing failed: Extracted content is too short', \Mockery::subset(['document_id' => $document->id]));
    }
}

<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentChunkStorageService;
use App\Services\ExtractionDispatcherService;
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

    public function test_job_sets_status_to_pending_approval_on_successful_extraction(): void
    {
        $document = Document::factory()->uploaded()->create();

        $this->mock(ExtractionDispatcherService::class)
            ->shouldReceive('extract')
            ->andReturn(['text' => str_repeat('word ', 30), 'extraction_method' => 'pdftotext', 'confidence' => null]);

        $this->mock(DocumentChunkStorageService::class)
            ->shouldReceive('store');

        app()->call([new ProcessDocumentJob($document), 'handle']);

        $this->assertSame(DocumentStatus::PendingApproval, $document->fresh()->status);
    }

    public function test_job_stores_extraction_method_on_success(): void
    {
        $document = Document::factory()->uploaded()->create();

        $this->mock(ExtractionDispatcherService::class)
            ->shouldReceive('extract')
            ->andReturn(['text' => str_repeat('word ', 30), 'extraction_method' => 'tesseract', 'confidence' => 85.5]);

        $this->mock(DocumentChunkStorageService::class)
            ->shouldReceive('store');

        app()->call([new ProcessDocumentJob($document), 'handle']);

        $this->assertSame('tesseract', $document->fresh()->extraction_method);
        $this->assertSame(85.5, $document->fresh()->ocr_confidence);
    }

    public function test_job_clears_status_message_on_success(): void
    {
        $document = Document::factory()->uploaded()->create([
            'status_message' => 'Retrying... (attempt 2 of 3)',
        ]);

        $this->mock(ExtractionDispatcherService::class)
            ->shouldReceive('extract')
            ->andReturn(['text' => str_repeat('word ', 30), 'extraction_method' => 'pdftotext', 'confidence' => null]);

        $this->mock(DocumentChunkStorageService::class)
            ->shouldReceive('store');

        app()->call([new ProcessDocumentJob($document), 'handle']);

        $this->assertNull($document->fresh()->status_message);
    }

    public function test_job_sets_status_to_failed_when_extraction_throws(): void
    {
        $document = Document::factory()->uploaded()->create();

        $this->mock(ExtractionDispatcherService::class)
            ->shouldReceive('extract')
            ->andThrow(new \Exception('Unsupported file type'));

        $this->mock(DocumentChunkStorageService::class)
            ->shouldNotReceive('store');

        app()->call([new ProcessDocumentJob($document), 'handle']);

        $this->assertSame(DocumentStatus::Failed, $document->fresh()->status);
    }

    public function test_job_sets_status_message_when_extraction_fails(): void
    {
        $document = Document::factory()->uploaded()->create();

        $this->mock(ExtractionDispatcherService::class)
            ->shouldReceive('extract')
            ->andThrow(new \Exception('Unsupported file type'));

        $this->mock(DocumentChunkStorageService::class);

        app()->call([new ProcessDocumentJob($document), 'handle']);

        $this->assertNotNull($document->fresh()->status_message);
    }

    public function test_job_sets_status_to_failed_when_content_is_too_short(): void
    {
        $document = Document::factory()->uploaded()->create();

        $this->mock(ExtractionDispatcherService::class)
            ->shouldReceive('extract')
            ->andReturn(['text' => 'too short', 'extraction_method' => 'tesseract', 'confidence' => 10.0]);

        $this->mock(DocumentChunkStorageService::class)
            ->shouldNotReceive('store');

        app()->call([new ProcessDocumentJob($document), 'handle']);

        $this->assertSame(DocumentStatus::Failed, $document->fresh()->status);
    }

    public function test_job_sets_status_message_when_content_is_too_short(): void
    {
        $document = Document::factory()->uploaded()->create();

        $this->mock(ExtractionDispatcherService::class)
            ->shouldReceive('extract')
            ->andReturn(['text' => 'short', 'extraction_method' => 'tesseract', 'confidence' => 10.0]);

        $this->mock(DocumentChunkStorageService::class);

        app()->call([new ProcessDocumentJob($document), 'handle']);

        $this->assertNotNull($document->fresh()->status_message);
    }

    public function test_job_propagates_exception_when_chunk_storage_throws(): void
    {
        $document = Document::factory()->uploaded()->create();

        $this->mock(ExtractionDispatcherService::class)
            ->shouldReceive('extract')
            ->andReturn(['text' => str_repeat('word ', 30), 'extraction_method' => 'pdftotext', 'confidence' => null]);

        $this->mock(DocumentChunkStorageService::class)
            ->shouldReceive('store')
            ->andThrow(new \Exception('Embedding service unavailable'));

        $this->expectException(\Exception::class);

        app()->call([new ProcessDocumentJob($document), 'handle']);
    }

    public function test_failed_method_sets_status_to_failed_with_message(): void
    {
        $document = Document::factory()->processing()->create();
        $job = new ProcessDocumentJob($document);

        $job->failed(new \Exception('Queue timeout'));

        $this->assertSame(DocumentStatus::Failed, $document->fresh()->status);
        $this->assertNotNull($document->fresh()->status_message);
    }

    public function test_failed_method_does_not_overwrite_existing_failure_message(): void
    {
        $document = Document::factory()->failed()->create([
            'status_message' => 'Could not read this file. Remove it and try a different file.',
        ]);
        $job = new ProcessDocumentJob($document);

        $job->failed(new \Exception('Some error'));

        $this->assertSame(
            'Could not read this file. Remove it and try a different file.',
            $document->fresh()->status_message,
        );
    }

    public function test_job_logs_error_when_extraction_fails(): void
    {
        Log::spy();

        $document = Document::factory()->uploaded()->create();

        $this->mock(ExtractionDispatcherService::class)
            ->shouldReceive('extract')
            ->andThrow(new \Exception('Unsupported file type'));

        $this->mock(DocumentChunkStorageService::class);

        app()->call([new ProcessDocumentJob($document), 'handle']);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Extraction failed', \Mockery::subset(['document_id' => $document->id]));
    }

    public function test_job_logs_error_when_content_is_too_short(): void
    {
        Log::spy();

        $document = Document::factory()->uploaded()->create();

        $this->mock(ExtractionDispatcherService::class)
            ->shouldReceive('extract')
            ->andReturn(['text' => 'short', 'extraction_method' => 'tesseract', 'confidence' => 10.0]);

        $this->mock(DocumentChunkStorageService::class);

        app()->call([new ProcessDocumentJob($document), 'handle']);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Processing failed: no readable content found', \Mockery::subset(['document_id' => $document->id]));
    }
}

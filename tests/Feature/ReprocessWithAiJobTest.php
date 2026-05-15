<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Jobs\ReprocessWithAiJob;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Services\DocumentChunkStorageService;
use App\Services\GeminiOcrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ReprocessWithAiJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_replaces_chunks_and_sets_pending_approval_on_success(): void
    {
        $document = Document::factory()->pendingApproval()->create([
            'extraction_method' => 'tesseract',
        ]);
        DocumentChunk::factory()->for($document)->count(3)->sequence(
            ['chunk_index' => 0],
            ['chunk_index' => 1],
            ['chunk_index' => 2],
        )->create();

        $this->mock(GeminiOcrService::class)
            ->shouldReceive('extract')
            ->andReturn(['text' => str_repeat('word ', 30), 'extraction_method' => 'gemini', 'confidence' => null]);

        $this->mock(DocumentChunkStorageService::class)
            ->shouldReceive('store')
            ->once();

        app()->call([new ReprocessWithAiJob($document), 'handle']);

        $this->assertSame(DocumentStatus::PendingApproval, $document->fresh()->status);
        $this->assertSame('gemini', $document->fresh()->extraction_method);
        $this->assertNull($document->fresh()->ocr_confidence);
        $this->assertDatabaseCount('document_chunks', 0);
    }

    public function test_job_clears_status_message_on_success(): void
    {
        $document = Document::factory()->pendingApproval()->create([
            'status_message' => 'AI extraction is temporarily unavailable.',
        ]);

        $this->mock(GeminiOcrService::class)
            ->shouldReceive('extract')
            ->andReturn(['text' => str_repeat('word ', 30), 'extraction_method' => 'gemini', 'confidence' => null]);

        $this->mock(DocumentChunkStorageService::class)->shouldReceive('store');

        app()->call([new ReprocessWithAiJob($document), 'handle']);

        $this->assertNull($document->fresh()->status_message);
    }

    public function test_job_sets_status_to_failed_when_gemini_throws(): void
    {
        $document = Document::factory()->pendingApproval()->create();

        $this->mock(GeminiOcrService::class)
            ->shouldReceive('extract')
            ->andThrow(new \Exception('High demand'));

        $this->mock(DocumentChunkStorageService::class)
            ->shouldNotReceive('store');

        app()->call([new ReprocessWithAiJob($document), 'handle']);

        $this->assertSame(DocumentStatus::Failed, $document->fresh()->status);
        $this->assertNotNull($document->fresh()->status_message);
    }

    public function test_failed_method_sets_status_to_pending_approval_with_message(): void
    {
        $document = Document::factory()->processing()->create();
        $job = new ReprocessWithAiJob($document);

        $job->failed(new \Exception('High demand'));

        $this->assertSame(DocumentStatus::PendingApproval, $document->fresh()->status);
        $this->assertNotNull($document->fresh()->status_message);
    }

    public function test_failed_method_logs_error(): void
    {
        Log::spy();

        $document = Document::factory()->processing()->create();
        $job = new ReprocessWithAiJob($document);

        $job->failed(new \Exception('High demand'));

        Log::shouldHaveReceived('error')
            ->once()
            ->with('ReprocessWithAiJob permanently failed', \Mockery::subset(['document_id' => $document->id]));
    }
}

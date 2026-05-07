<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $file = \Illuminate\Http\UploadedFile::fake()->create('report.pdf', 512, 'application/pdf');

        $this->actingAs($user)->postJson('/documents', ['file' => $file]);

        Queue::assertPushed(ProcessDocumentJob::class, function ($job) use ($user) {
            return $job->document->user_id === $user->id;
        });
    }

    public function test_job_updates_document_status_to_processing(): void
    {
        $document = Document::factory()->uploaded()->create();

        (new ProcessDocumentJob($document))->handle();

        $this->assertSame('processing', $document->fresh()->status);
    }

    public function test_job_logs_processing_started(): void
    {
        Log::spy();

        $document = Document::factory()->uploaded()->create();

        (new ProcessDocumentJob($document))->handle();

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Processing started', ['document_id' => $document->id]);
    }
}

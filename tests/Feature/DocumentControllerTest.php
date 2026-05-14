<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Jobs\ReprocessWithAiJob;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\User;
use App\Services\DocumentChunkStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    // --- approve ---

    public function test_approve_sets_document_to_ready(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->pendingApproval()->for($user)->create();

        $this->actingAs($user)->post("/documents/{$document->id}/approve");

        $this->assertSame(DocumentStatus::Ready, $document->fresh()->status);
    }

    public function test_approve_with_edited_text_replaces_chunks(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->pendingApproval()->for($user)->create();
        DocumentChunk::factory()->for($document)->count(2)->create();

        $this->mock(DocumentChunkStorageService::class)
            ->shouldReceive('store')
            ->once();

        $this->actingAs($user)
            ->post("/documents/{$document->id}/approve", ['text' => str_repeat('word ', 30)]);

        $this->assertDatabaseCount('document_chunks', 0);
        $this->assertSame(DocumentStatus::Ready, $document->fresh()->status);
    }

    public function test_approve_without_text_does_not_rechunk(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->pendingApproval()->for($user)->create();
        DocumentChunk::factory()->for($document)->count(2)->sequence(
            ['chunk_index' => 0],
            ['chunk_index' => 1],
        )->create();

        $this->mock(DocumentChunkStorageService::class)
            ->shouldNotReceive('store');

        $this->actingAs($user)->post("/documents/{$document->id}/approve");

        $this->assertDatabaseCount('document_chunks', 2);
    }

    // --- reprocess ---

    public function test_reprocess_dispatches_job_and_sets_processing(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $document = Document::factory()->pendingApproval()->for($user)->create();

        $this->actingAs($user)->post("/documents/{$document->id}/reprocess");

        Queue::assertPushed(ReprocessWithAiJob::class, fn ($job) => $job->document->id === $document->id);
        $this->assertSame(DocumentStatus::Processing, $document->fresh()->status);
        $this->assertNotNull($document->fresh()->ai_last_attempted_at);
    }

    public function test_reprocess_is_rejected_within_two_hour_cooldown(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $document = Document::factory()->pendingApproval()->for($user)->create([
            'ai_last_attempted_at' => now()->subHour(),
        ]);

        $this->actingAs($user)
            ->post("/documents/{$document->id}/reprocess")
            ->assertSessionHasErrors('ai');

        Queue::assertNothingPushed();
    }

    public function test_reprocess_is_allowed_after_two_hour_cooldown(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $document = Document::factory()->pendingApproval()->for($user)->create([
            'ai_last_attempted_at' => now()->subHours(3),
        ]);

        $this->actingAs($user)->post("/documents/{$document->id}/reprocess");

        Queue::assertPushed(ReprocessWithAiJob::class);
    }

    // --- preview ---

    public function test_preview_returns_combined_chunk_text(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->pendingApproval()->for($user)->create([
            'extraction_method' => 'tesseract',
            'ocr_confidence' => 78.555,
        ]);
        DocumentChunk::factory()->for($document)->create(['chunk_index' => 0, 'content' => 'First chunk']);
        DocumentChunk::factory()->for($document)->create(['chunk_index' => 1, 'content' => 'Second chunk']);

        $response = $this->actingAs($user)
            ->getJson("/documents/{$document->id}/preview");

        $response->assertOk()
            ->assertJson([
                'text' => "First chunk\n\nSecond chunk",
                'extractionMethod' => 'tesseract',
                'ocrConfidence' => 78.6,
            ]);
    }

    // --- file ---

    public function test_file_returns_stored_document(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('report.pdf', 10, 'application/pdf');
        $path = $file->store('documents', 'local');

        $document = Document::factory()->pendingApproval()->for($user)->create(['path' => $path]);

        $this->actingAs($user)
            ->get("/documents/{$document->id}/file")
            ->assertOk();
    }
}

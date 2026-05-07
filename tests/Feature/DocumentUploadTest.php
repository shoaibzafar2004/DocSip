<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_a_document(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('report.pdf', 512, 'application/pdf');

        $response = $this->actingAs($user)->postJson('/documents', ['file' => $file]);

        $response->assertCreated();
        $response->assertJsonStructure(['id', 'name', 'status', 'createdAt']);
        $response->assertJsonFragment(['name' => 'report.pdf', 'status' => 'uploaded']);

        $this->assertDatabaseHas('documents', [
            'user_id' => $user->id,
            'name' => 'report.pdf',
            'status' => 'uploaded',
        ]);

        Storage::disk('local')->assertExists(
            Document::first()->path,
        );
    }

    public function test_unauthenticated_user_cannot_upload_a_document(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('report.pdf', 512, 'application/pdf');

        $response = $this->postJson('/documents', ['file' => $file]);

        $response->assertUnauthorized();
    }

    public function test_upload_requires_a_file(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/documents', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_upload_rejects_files_over_30mb(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('huge.pdf', 31_000, 'application/pdf');

        $response = $this->actingAs($user)->postJson('/documents', ['file' => $file]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_upload_rejects_disallowed_file_types(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('data.json', 10, 'application/json');

        $response = $this->actingAs($user)->postJson('/documents', ['file' => $file]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_dashboard_returns_documents_and_stats_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        Document::factory()->count(2)->ready()->for($user)->create();
        Document::factory()->count(1)->processing()->for($user)->create();
        Document::factory()->count(1)->uploaded()->for($user)->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('dashboard')
                ->has('documents', 4)
                ->where('stats.total', 4)
                ->where('stats.ready', 2)
                ->where('stats.processing', 1)
                ->where('stats.uploaded', 1),
        );
    }

    public function test_user_can_delete_their_own_document(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create(['path' => 'documents/test.pdf']);
        Storage::disk('local')->put($document->path, 'dummy content');

        $response = $this->actingAs($user)->deleteJson("/documents/{$document->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($document->path);
    }

    public function test_user_cannot_delete_another_users_document(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $document = Document::factory()->for($owner)->create();

        $response = $this->actingAs($other)->deleteJson("/documents/{$document->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('documents', ['id' => $document->id]);
    }

    public function test_unauthenticated_user_cannot_delete_a_document(): void
    {
        $document = Document::factory()->create();

        $response = $this->deleteJson("/documents/{$document->id}");

        $response->assertUnauthorized();
    }
}

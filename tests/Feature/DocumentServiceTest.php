<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DocumentService();
    }

    public function test_upload_stores_file_and_creates_document_record(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('report.pdf', 512, 'application/pdf');

        $document = $this->service->upload($user, $file);

        $this->assertInstanceOf(Document::class, $document);
        $this->assertDatabaseHas('documents', [
            'user_id' => $user->id,
            'name' => 'report.pdf',
            'status' => 'uploaded',
            'mime_type' => 'application/pdf',
        ]);
        Storage::disk('local')->assertExists($document->path);
    }

    public function test_upload_throws_exception_when_storage_fails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to store the uploaded file.');

        $user = User::factory()->create();

        $file = $this->createMock(UploadedFile::class);
        $file->method('store')->willReturn(false);

        $this->service->upload($user, $file);
    }

    public function test_delete_removes_file_from_storage_and_database(): void
    {
        Storage::fake('local');

        $document = Document::factory()->create(['path' => 'documents/test.pdf']);
        Storage::disk('local')->put($document->path, 'dummy content');

        $this->service->delete($document);

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($document->path);
    }

    public function test_delete_removes_database_record_even_when_file_does_not_exist(): void
    {
        Storage::fake('local');

        $document = Document::factory()->create(['path' => 'documents/missing.pdf']);

        $this->service->delete($document);

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }

    public function test_get_for_dashboard_returns_correctly_shaped_data(): void
    {
        $user = User::factory()->create();
        Document::factory()->for($user)->uploaded()->create(['name' => 'report.pdf']);

        $documents = $this->service->getForDashboard($user);

        $this->assertCount(1, $documents);
        $this->assertArrayHasKey('id', $documents->first());
        $this->assertArrayHasKey('name', $documents->first());
        $this->assertArrayHasKey('status', $documents->first());
        $this->assertArrayHasKey('createdAt', $documents->first());
    }

    public function test_get_for_dashboard_only_returns_the_users_own_documents(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Document::factory()->count(3)->for($user)->create();
        Document::factory()->count(2)->for($other)->create();

        $documents = $this->service->getForDashboard($user);

        $this->assertCount(3, $documents);
    }

    public function test_get_for_dashboard_returns_documents_in_latest_order(): void
    {
        $user = User::factory()->create();
        $oldest = Document::factory()->for($user)->create(['created_at' => now()->subDays(2)]);
        Document::factory()->for($user)->create(['created_at' => now()->subDay()]);
        $newest = Document::factory()->for($user)->create(['created_at' => now()]);

        $documents = $this->service->getForDashboard($user);

        $this->assertSame($newest->id, $documents->first()['id']);
        $this->assertSame($oldest->id, $documents->last()['id']);
    }
}

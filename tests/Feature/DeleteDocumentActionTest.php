<?php

namespace Tests\Feature;

use App\Actions\DeleteDocumentAction;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteDocumentActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_delete_their_document(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create();

        $service = $this->createMock(DocumentService::class);
        $service->expects($this->once())->method('delete')->with($document);

        $this->actingAs($user);

        (new DeleteDocumentAction($service))->handle($document);
    }

    public function test_other_user_cannot_delete_someone_elses_document(): void
    {
        $this->expectException(AuthorizationException::class);

        $owner = User::factory()->create();
        $other = User::factory()->create();
        $document = Document::factory()->for($owner)->create();

        $service = $this->createMock(DocumentService::class);
        $service->expects($this->never())->method('delete');

        $this->actingAs($other);

        (new DeleteDocumentAction($service))->handle($document);
    }
}

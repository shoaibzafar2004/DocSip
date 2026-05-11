<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Document;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_chat_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('conversations'));

        $response->assertOk();
    }

    public function test_unauthenticated_user_cannot_view_chat_index(): void
    {
        $response = $this->get(route('conversations'));

        $response->assertRedirect();
    }

    public function test_index_only_returns_users_conversations(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Conversation::factory()->count(2)->for($user)->create();
        Conversation::factory()->count(3)->for($other)->create();

        $response = $this->actingAs($user)->get(route('conversations'));

        $response->assertInertia(fn ($page) => $page->has('conversations', 2));
    }

    public function test_user_can_create_a_conversation_with_ready_documents(): void
    {
        $user = User::factory()->create();
        $documents = Document::factory()->count(2)->ready()->for($user)->create();

        $response = $this->actingAs($user)->postJson(route('conversations.store'), [
            'document_ids' => $documents->pluck('id')->all(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('conversations', ['user_id' => $user->id]);
    }

    public function test_store_rejects_documents_not_belonging_to_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $document = Document::factory()->ready()->for($other)->create();

        $response = $this->actingAs($user)->postJson(route('conversations.store'), [
            'document_ids' => [$document->id],
        ]);

        $response->assertStatus(422);
    }

    public function test_store_rejects_documents_that_are_not_ready(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->processing()->for($user)->create();

        $response = $this->actingAs($user)->postJson(route('conversations.store'), [
            'document_ids' => [$document->id],
        ]);

        $response->assertStatus(422);
    }

    public function test_store_requires_at_least_one_document(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('conversations.store'), [
            'document_ids' => [],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['document_ids']);
    }

    public function test_user_can_view_their_own_conversation(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('conversations.show', $conversation));

        $response->assertOk();
    }

    public function test_user_cannot_view_another_users_conversation(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $conversation = Conversation::factory()->for($owner)->create();

        $response = $this->actingAs($other)->get(route('conversations.show', $conversation));

        $response->assertForbidden();
    }

    public function test_show_includes_messages_and_documents(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();
        Message::factory()->count(3)->for($conversation)->create();
        $document = Document::factory()->ready()->for($user)->create();
        $conversation->documents()->attach($document->id);

        $response = $this->actingAs($user)->get(route('conversations.show', $conversation));

        $response->assertInertia(fn ($page) => $page
            ->has('messages', 3)
            ->has('attachedDocuments', 1)
        );
    }
}

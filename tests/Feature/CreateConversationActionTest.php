<?php

namespace Tests\Feature;

use App\Actions\CreateConversationAction;
use App\Models\Conversation;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateConversationActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_conversation_for_the_user(): void
    {
        $user = User::factory()->create();
        $documents = Document::factory()->count(2)->ready()->for($user)->create();

        $conversation = (new CreateConversationAction)->handle($user, $documents->pluck('id')->all());

        $this->assertDatabaseHas('conversations', [
            'user_id' => $user->id,
            'title' => null,
        ]);
        $this->assertSame($user->id, $conversation->user_id);
    }

    public function test_it_attaches_documents_to_the_conversation(): void
    {
        $user = User::factory()->create();
        $documents = Document::factory()->count(2)->ready()->for($user)->create();

        $conversation = (new CreateConversationAction)->handle($user, $documents->pluck('id')->all());

        $this->assertCount(2, $conversation->documents);
        foreach ($documents as $document) {
            $this->assertDatabaseHas('conversation_document', [
                'conversation_id' => $conversation->id,
                'document_id' => $document->id,
            ]);
        }
    }

    public function test_it_returns_the_created_conversation(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->ready()->for($user)->create();

        $result = (new CreateConversationAction)->handle($user, [$document->id]);

        $this->assertInstanceOf(Conversation::class, $result);
    }
}

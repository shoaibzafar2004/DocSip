<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Document;
use App\Models\User;
use App\Services\AnswerService;
use App\Services\DocumentQueryService;
use App\Services\EmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(EmbeddingService::class)
            ->shouldReceive('embedMany')
            ->andReturn([array_fill(0, 768, 0.1)]);

        $this->mock(DocumentQueryService::class)
            ->shouldReceive('query')
            ->andReturn(['Some relevant context.']);

        $this->mock(AnswerService::class)
            ->shouldReceive('answer')
            ->andReturn('This is the assistant answer.');
    }

    public function test_unauthenticated_user_cannot_send_message(): void
    {
        $conversation = Conversation::factory()->create();

        $response = $this->postJson(route('messages.store', $conversation), [
            'content' => 'Hello',
        ]);

        $response->assertUnauthorized();
    }

    public function test_user_cannot_send_message_to_another_users_conversation(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $conversation = Conversation::factory()->for($owner)->create();

        $response = $this->actingAs($other)->postJson(route('messages.store', $conversation), [
            'content' => 'Hello',
        ]);

        $response->assertForbidden();
    }

    public function test_store_creates_user_message(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();
        $conversation->documents()->attach(Document::factory()->ready()->for($user)->create());

        $this->actingAs($user)->postJson(route('messages.store', $conversation), [
            'content' => 'What is in the document?',
        ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'What is in the document?',
        ]);
    }

    public function test_store_creates_assistant_message(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();
        $conversation->documents()->attach(Document::factory()->ready()->for($user)->create());

        $this->actingAs($user)->postJson(route('messages.store', $conversation), [
            'content' => 'What is in the document?',
        ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'This is the assistant answer.',
        ]);
    }

    public function test_store_returns_201_with_both_messages(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();
        $conversation->documents()->attach(Document::factory()->ready()->for($user)->create());

        $response = $this->actingAs($user)->postJson(route('messages.store', $conversation), [
            'content' => 'What is in the document?',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'userMessage' => ['id', 'content', 'role'],
            'assistantMessage' => ['id', 'content', 'role'],
        ]);
    }

    public function test_store_requires_content(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();
        $conversation->documents()->attach(Document::factory()->ready()->for($user)->create());

        $response = $this->actingAs($user)->postJson(route('messages.store', $conversation), []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['content']);
    }

    public function test_locked_conversation_returns_403(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();

        $response = $this->actingAs($user)->postJson(route('messages.store', $conversation), [
            'content' => 'What is in the document?',
        ]);

        $response->assertForbidden();
    }
}

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
            ->shouldReceive('stream')
            ->andReturnUsing(fn () => (function () { yield 'This is the assistant answer.'; })());
    }

    public function test_unauthenticated_user_cannot_send_message(): void
    {
        $conversation = Conversation::factory()->create();

        $this->postJson(route('messages.store', $conversation), ['content' => 'Hello'])
            ->assertUnauthorized();
    }

    public function test_user_cannot_send_message_to_another_users_conversation(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $conversation = Conversation::factory()->for($owner)->create();

        $this->actingAs($other)
            ->postJson(route('messages.store', $conversation), ['content' => 'Hello'])
            ->assertForbidden();
    }

    public function test_store_creates_user_message(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();
        $conversation->documents()->attach(Document::factory()->ready()->for($user)->create());

        $this->actingAs($user)
            ->post(route('messages.store', $conversation), ['content' => 'What is in the document?'])
            ->streamedContent();

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

        $this->actingAs($user)
            ->post(route('messages.store', $conversation), ['content' => 'What is in the document?'])
            ->streamedContent();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'This is the assistant answer.',
        ]);
    }

    public function test_store_streams_sse_events(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();
        $conversation->documents()->attach(Document::factory()->ready()->for($user)->create());

        $content = $this->actingAs($user)
            ->post(route('messages.store', $conversation), ['content' => 'What is in the document?'])
            ->assertOk()
            ->assertHeader('Content-Type', 'text/event-stream; charset=utf-8')
            ->streamedContent();

        $this->assertStringContainsString('"type":"user"', $content);
        $this->assertStringContainsString('"type":"chunk"', $content);
        $this->assertStringContainsString('"type":"done"', $content);
    }

    public function test_store_requires_content(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();
        $conversation->documents()->attach(Document::factory()->ready()->for($user)->create());

        $this->actingAs($user)
            ->postJson(route('messages.store', $conversation), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    }

    public function test_locked_conversation_returns_403(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson(route('messages.store', $conversation), ['content' => 'Hello'])
            ->assertForbidden();
    }
}

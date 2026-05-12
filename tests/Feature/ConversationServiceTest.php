<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Document;
use App\Models\Message;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ConversationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ConversationService;
    }

    public function test_get_for_sidebar_returns_only_users_conversations(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Conversation::factory()->count(3)->for($user)->create();
        Conversation::factory()->count(2)->for($other)->create();

        $result = $this->service->getForSidebar($user);

        $this->assertCount(3, $result);
        $result->each(fn ($c) => $this->assertSame($user->id, $c->user_id));
    }

    public function test_get_for_sidebar_returns_latest_first(): void
    {
        $user = User::factory()->create();
        $oldest = Conversation::factory()->for($user)->create(['created_at' => now()->subDays(2)]);
        $newest = Conversation::factory()->for($user)->create(['created_at' => now()]);

        $result = $this->service->getForSidebar($user);

        $this->assertSame($newest->id, $result->first()->id);
        $this->assertSame($oldest->id, $result->last()->id);
    }

    public function test_get_for_sidebar_returns_at_most_20(): void
    {
        $user = User::factory()->create();
        Conversation::factory()->count(25)->for($user)->create();

        $result = $this->service->getForSidebar($user);

        $this->assertCount(20, $result);
    }

    public function test_get_with_messages_loads_messages_in_oldest_order(): void
    {
        $conversation = Conversation::factory()->create();
        $newer = Message::factory()->for($conversation)->create(['created_at' => now()]);
        $older = Message::factory()->for($conversation)->create(['created_at' => now()->subMinute()]);

        $result = $this->service->getWithMessages($conversation);

        $this->assertSame($older->id, $result->messages->first()->id);
        $this->assertSame($newer->id, $result->messages->last()->id);
    }

    public function test_get_with_messages_loads_documents(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();
        $documents = Document::factory()->count(2)->ready()->for($user)->create();
        $conversation->documents()->attach($documents->pluck('id'));

        $result = $this->service->getWithMessages($conversation);

        $this->assertCount(2, $result->documents);
    }
}

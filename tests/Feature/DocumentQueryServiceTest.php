<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\Message;
use App\Models\User;
use App\Services\DocumentQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pgvector\Laravel\Vector;
use Tests\TestCase;

class DocumentQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_array_of_content_strings(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();
        $document = Document::factory()->ready()->for($user)->create();
        $conversation->documents()->attach($document->id);

        DocumentChunk::factory()->for($document)->create([
            'content' => 'Some relevant content.',
            'embedding' => new Vector(array_fill(0, 768, 0.5)),
        ]);

        $message = Message::factory()->for($conversation)->create([
            'role' => 'user',
            'embedding' => new Vector(array_fill(0, 768, 0.5)),
        ]);

        $result = (new DocumentQueryService)->query($message);

        $this->assertIsArray($result);
        $this->assertContains('Some relevant content.', $result);
    }

    public function test_only_returns_chunks_from_conversation_documents(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();

        $attachedDoc = Document::factory()->ready()->for($user)->create();
        $otherDoc = Document::factory()->ready()->for($user)->create();
        $conversation->documents()->attach($attachedDoc->id);

        $embedding = new Vector(array_fill(0, 768, 0.5));

        DocumentChunk::factory()->for($attachedDoc)->create([
            'content' => 'Attached document content.',
            'embedding' => $embedding,
        ]);

        DocumentChunk::factory()->for($otherDoc)->create([
            'content' => 'Other document content.',
            'embedding' => $embedding,
        ]);

        $message = Message::factory()->for($conversation)->create([
            'role' => 'user',
            'embedding' => $embedding,
        ]);

        $result = (new DocumentQueryService)->query($message);

        $this->assertContains('Attached document content.', $result);
        $this->assertNotContains('Other document content.', $result);
    }

    public function test_returns_at_most_five_chunks(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();
        $document = Document::factory()->ready()->for($user)->create();
        $conversation->documents()->attach($document->id);

        $embedding = new Vector(array_fill(0, 768, 0.5));

        foreach (range(0, 9) as $i) {
            DocumentChunk::factory()->for($document)->create([
                'chunk_index' => $i,
                'embedding' => $embedding,
            ]);
        }

        $message = Message::factory()->for($conversation)->create([
            'role' => 'user',
            'embedding' => $embedding,
        ]);

        $result = (new DocumentQueryService)->query($message);

        $this->assertLessThanOrEqual(5, count($result));
    }
}

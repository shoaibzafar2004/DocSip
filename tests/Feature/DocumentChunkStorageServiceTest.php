<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Services\DocumentChunkingService;
use App\Services\DocumentChunkStorageService;
use App\Services\EmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentChunkStorageServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(): DocumentChunkStorageService
    {
        return new DocumentChunkStorageService(
            new DocumentChunkingService,
            app(EmbeddingService::class),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(EmbeddingService::class)
            ->shouldReceive('embedMany')
            ->andReturnUsing(fn (array $texts) => array_fill(0, count($texts), array_fill(0, 768, 0.1)));
    }

    public function test_store_creates_chunks_for_document(): void
    {
        $document = Document::factory()->processing()->create();

        $this->makeService()->store($document, str_repeat('word ', 300));

        $this->assertGreaterThan(0, $document->chunks()->count());
    }

    public function test_chunks_have_sequential_indexes(): void
    {
        $document = Document::factory()->processing()->create();

        $this->makeService()->store($document, str_repeat('word ', 300));

        $indexes = $document->chunks()->orderBy('chunk_index')->pluck('chunk_index')->toArray();

        $this->assertSame(range(0, count($indexes) - 1), $indexes);
    }

    public function test_chunks_have_embeddings_stored(): void
    {
        $document = Document::factory()->processing()->create();

        $this->makeService()->store($document, str_repeat('word ', 300));

        $document->chunks()->each(function ($chunk) {
            $this->assertNotNull($chunk->embedding);
        });
    }

    public function test_store_rolls_back_all_chunks_on_failure(): void
    {
        $document = Document::factory()->processing()->create();

        $this->mock(EmbeddingService::class)
            ->shouldReceive('embedMany')
            ->andThrow(new \Exception('OpenAI API error'));

        try {
            $this->makeService()->store($document, str_repeat('word ', 300));
        } catch (\Exception) {
            // expected — exception propagates after rollback
        }

        $this->assertSame(0, $document->chunks()->count());
    }

    public function test_short_text_still_stores_a_chunk(): void
    {
        $document = Document::factory()->processing()->create();

        $this->makeService()->store($document, str_repeat('word ', 20));

        $this->assertSame(1, $document->chunks()->count());
        $this->assertSame(0, $document->chunks()->first()->chunk_index);
    }

    public function test_embeddings_are_called_once_for_all_chunks(): void
    {
        $document = Document::factory()->processing()->create();

        $this->mock(EmbeddingService::class)
            ->shouldReceive('embedMany')
            ->once()
            ->andReturnUsing(fn (array $texts) => array_fill(0, count($texts), array_fill(0, 768, 0.1)));

        $this->makeService()->store($document, str_repeat('word ', 300));
    }
}

<?php

namespace Tests\Feature;

use App\Services\EmbeddingService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmbeddingServiceTest extends TestCase
{
    public function test_embed_many_returns_array_of_vectors(): void
    {
        $fakeEmbedding = array_fill(0, 768, 0.1);

        Http::fake([
            '*/api/embed' => Http::response([
                'embeddings' => [$fakeEmbedding, $fakeEmbedding],
            ]),
        ]);

        $result = (new EmbeddingService)->embedMany(['hello world', 'second chunk']);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertCount(768, $result[0]);
        $this->assertCount(768, $result[1]);
    }

    public function test_embed_many_sends_correct_model_and_input(): void
    {
        Http::fake([
            '*/api/embed' => Http::response([
                'embeddings' => [array_fill(0, 768, 0.0)],
            ]),
        ]);

        (new EmbeddingService)->embedMany(['test input']);

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), '/api/embed')
                && $request->data()['model'] === 'nomic-embed-text'
                && $request->data()['input'] === ['test input'];
        });
    }

    public function test_embed_many_returns_one_vector_per_input(): void
    {
        $texts = ['chunk one', 'chunk two', 'chunk three'];

        Http::fake([
            '*/api/embed' => Http::response([
                'embeddings' => array_fill(0, count($texts), array_fill(0, 768, 0.0)),
            ]),
        ]);

        $result = (new EmbeddingService)->embedMany($texts);

        $this->assertCount(count($texts), $result);
    }

    public function test_embed_many_throws_on_failed_response(): void
    {
        Http::fake([
            '*/api/embed' => Http::response('Service Unavailable', 503),
        ]);

        $this->expectException(\RuntimeException::class);

        (new EmbeddingService)->embedMany(['test']);
    }
}

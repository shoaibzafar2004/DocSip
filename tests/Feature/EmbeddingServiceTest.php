<?php

namespace Tests\Feature;

use App\Services\EmbeddingService;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Resources\Embeddings;
use OpenAI\Responses\Embeddings\CreateResponse;
use Tests\TestCase;

class EmbeddingServiceTest extends TestCase
{
    public function test_embed_many_returns_array_of_vectors(): void
    {
        $fakeEmbedding = array_fill(0, 1536, 0.1);

        OpenAI::fake([
            CreateResponse::fake([
                'data' => [
                    ['embedding' => $fakeEmbedding, 'index' => 0, 'object' => 'embedding'],
                    ['embedding' => $fakeEmbedding, 'index' => 1, 'object' => 'embedding'],
                ],
            ]),
        ]);

        $result = (new EmbeddingService)->embedMany(['hello world', 'second chunk']);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertCount(1536, $result[0]);
        $this->assertCount(1536, $result[1]);
    }

    public function test_embed_many_uses_correct_model_and_sends_array_input(): void
    {
        OpenAI::fake([
            CreateResponse::fake([
                'data' => [
                    ['embedding' => array_fill(0, 1536, 0.0), 'index' => 0, 'object' => 'embedding'],
                ],
            ]),
        ]);

        (new EmbeddingService)->embedMany(['test input']);

        OpenAI::assertSent(Embeddings::class, function (string $method, array $parameters): bool {
            return $method === 'create'
                && $parameters['model'] === 'text-embedding-3-small'
                && $parameters['input'] === ['test input'];
        });
    }

    public function test_embed_many_returns_one_vector_per_input(): void
    {
        $texts = ['chunk one', 'chunk two', 'chunk three'];
        $fakeEmbedding = array_fill(0, 1536, 0.0);

        OpenAI::fake([
            CreateResponse::fake([
                'data' => array_map(
                    fn (int $i) => ['embedding' => $fakeEmbedding, 'index' => $i, 'object' => 'embedding'],
                    array_keys($texts),
                ),
            ]),
        ]);

        $result = (new EmbeddingService)->embedMany($texts);

        $this->assertCount(count($texts), $result);
    }
}

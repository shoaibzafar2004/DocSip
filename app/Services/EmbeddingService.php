<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    public function embedMany(array $texts): array
    {
        $embeddings = [];

        foreach (array_chunk($texts, 50) as $batch) {
            $response = Http::timeout(120)->post(config('services.ollama.url').'/api/embed', [
                'model' => 'nomic-embed-text',
                'input' => $batch,
            ]);

            if ($response->failed()) {
                throw new \RuntimeException('Ollama embedding request failed: '.$response->body());
            }

            $embeddings = array_merge($embeddings, $response->json('embeddings'));
        }

        return $embeddings;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    public function embedMany(array $texts): array
    {
        $response = Http::post(config('services.ollama.url').'/api/embed', [
            'model' => 'nomic-embed-text',
            'input' => $texts,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Ollama embedding request failed: '.$response->body());
        }

        return $response->json('embeddings');
    }
}

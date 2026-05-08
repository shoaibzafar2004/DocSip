<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\DB;

class DocumentChunkStorageService
{
    public function __construct(
        protected DocumentChunkingService $chunkingService,
        protected EmbeddingService $embeddingService
    ) {}

    public function store(Document $document, string $content): void
    {
        $chunks = $this->chunkingService->chunk($content);
        $embeddings = $this->embeddingService->embedMany($chunks);
        DB::transaction(function () use ($document, $chunks, $embeddings) {
            foreach ($chunks as $index => $chunk) {
                $document->chunks()->create([
                    'chunk_index' => $index,
                    'content' => $chunk,
                    'embedding' => $embeddings[$index],
                ]);
            }
        });
    }
}

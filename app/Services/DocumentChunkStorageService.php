<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\DB;

class DocumentChunkStorageService
{
    private const BATCH_SIZE = 50;

    public function __construct(
        protected DocumentChunkingService $chunkingService,
        protected EmbeddingService $embeddingService
    ) {}

    public function store(Document $document, string $content): void
    {
        $chunks = $this->chunkingService->chunk($content);

        foreach (array_chunk($chunks, self::BATCH_SIZE) as $batchIndex => $batch) {
            $embeddings = $this->embeddingService->embedMany($batch);
            $startIndex = $batchIndex * self::BATCH_SIZE;

            DB::transaction(function () use ($document, $batch, $embeddings, $startIndex) {
                foreach ($batch as $i => $chunk) {
                    $document->chunks()->create([
                        'chunk_index' => $startIndex + $i,
                        'content' => $chunk,
                        'embedding' => $embeddings[$i],
                    ]);
                }
            });
        }
    }
}

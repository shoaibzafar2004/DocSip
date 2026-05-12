<?php

namespace App\Services;

use App\Models\DocumentChunk;
use App\Models\Message;
use Pgvector\Laravel\Distance;

class DocumentQueryService
{
    public function query(Message $message): array
    {
        $documentIds = $message->conversation->documents->pluck('id');

        return DocumentChunk::whereIn('document_id', $documentIds)
            ->nearestNeighbors('embedding', $message->embedding, Distance::Cosine)
            ->take(5)
            ->get()
            ->pluck('content')
            ->all();
    }
}

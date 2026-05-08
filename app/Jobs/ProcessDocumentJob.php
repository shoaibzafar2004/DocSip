<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentChunkStorageService;
use App\Services\PdfExtractionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function __construct(public Document $document) {}

    public function handle(PdfExtractionService $pdfExtractor, DocumentChunkStorageService $chunkStorage): void
    {
        $this->document->update([
            'status' => 'processing',
            'status_message' => $this->attempts() > 1
                ? 'Retrying... (attempt '.$this->attempts().' of '.$this->tries.')'
                : null,
        ]);
        $path = Storage::path($this->document->path);
        try {
            $content = $pdfExtractor->extract($path);
        } catch (\Exception $e) {
            $this->document->update([
                'status' => 'failed',
                'status_message' => 'Could not read this PDF. Remove it and try a different file.',
            ]);
            Log::error('Text extraction failed', ['document_id' => $this->document->id, 'error' => $e->getMessage()]);
            $this->fail($e);

            return;
        }

        if (strlen(trim($content)) > 50) {
            $chunkStorage->store($this->document, $content);
            $this->document->update([
                'status' => 'ready',
                'status_message' => null,
            ]);
        } else {
            $this->document->update([
                'status' => 'failed',
                'status_message' => 'This PDF has no readable text. Remove it and try again.',
            ]);
            Log::error('Processing failed: Extracted content is too short', ['document_id' => $this->document->id]);
            $this->fail(new \RuntimeException('Extracted content is too short'));
        }
    }

    public function failed(\Throwable $exception): void
    {
        if ($this->document->fresh()->status === 'failed') {
            return;
        }

        $this->document->update([
            'status' => 'failed',
            'status_message' => 'Processing failed after multiple retries. Remove this file and upload it again.',
        ]);
        Log::error('Document processing permanently failed after all retries', [
            'document_id' => $this->document->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

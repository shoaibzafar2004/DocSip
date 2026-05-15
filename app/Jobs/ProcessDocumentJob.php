<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Services\DocumentChunkStorageService;
use App\Services\ExtractionDispatcherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    public int $tries = 3;

    public int $timeout = 900;

    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function __construct(public Document $document) {}

    public function handle(ExtractionDispatcherService $dispatcher, DocumentChunkStorageService $chunkStorage): void
    {
        $this->document->update([
            'status' => DocumentStatus::Processing,
            'status_message' => $this->attempts() > 1
                ? 'Retrying... (attempt '.$this->attempts().' of '.$this->tries.')'
                : null,
        ]);

        $path = Storage::path($this->document->path);

        try {
            $result = $dispatcher->extract($this->document, $path);
        } catch (\Exception $e) {
            $this->document->update([
                'status' => DocumentStatus::Failed,
                'status_message' => 'Could not read this file. Remove it and try a different file.',
            ]);
            Log::error('Extraction failed', ['document_id' => $this->document->id, 'error' => $e->getMessage()]);
            $this->fail($e);

            return;
        }

        if (strlen(trim($result['text'])) > 50) {
            $chunkStorage->store($this->document, $result['text']);
            $this->document->update([
                'status' => DocumentStatus::PendingApproval,
                'status_message' => null,
                'extraction_method' => $result['extraction_method'],
                'ocr_confidence' => $result['confidence'],
            ]);
        } else {
            $this->document->update([
                'status' => DocumentStatus::Failed,
                'status_message' => 'No readable text found in this file. Remove it and try again.',
            ]);
            Log::error('Processing failed: no readable content found', ['document_id' => $this->document->id]);
            $this->fail(new \RuntimeException('No readable content found'));
        }
    }

    public function failed(\Throwable $exception): void
    {
        if ($this->document->fresh()->status === DocumentStatus::Failed) {
            return;
        }

        $this->document->update([
            'status' => DocumentStatus::Failed,
            'status_message' => 'Processing failed after multiple retries. Remove this file and upload it again.',
        ]);
        Log::error('Document processing permanently failed after all retries', [
            'document_id' => $this->document->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

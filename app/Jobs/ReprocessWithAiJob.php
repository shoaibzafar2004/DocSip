<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Services\DocumentChunkStorageService;
use App\Services\GeminiOcrService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReprocessWithAiJob implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    public int $tries = 2;

    public function backoff(): array
    {
        return [60, 120];
    }

    public function __construct(public Document $document) {}

    public function handle(GeminiOcrService $geminiOcr, DocumentChunkStorageService $chunkStorage): void
    {
        $path = Storage::disk('local')->path($this->document->path);

        try {
            $result = $geminiOcr->extract($this->document, $path);
        } catch (\Exception $e) {
            $this->document->update([
                'status' => DocumentStatus::Failed,
                'status_message' => 'AI extraction failed. Remove the file and try again.',
            ]);
            Log::error('Gemini OCR failed', ['document_id' => $this->document->id, 'error' => $e->getMessage()]);
            $this->fail($e);

            return;
        }

        $this->document->chunks()->delete();
        $chunkStorage->store($this->document, $result['text']);

        $this->document->update([
            'status' => DocumentStatus::PendingApproval,
            'status_message' => null,
            'extraction_method' => $result['extraction_method'],
            'ocr_confidence' => null,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->document->update([
            'status' => DocumentStatus::PendingApproval,
            'status_message' => 'AI extraction is temporarily unavailable. Your extracted text is still available to review and approve. Try again in 2 hours.',
        ]);
        Log::error('ReprocessWithAiJob permanently failed', [
            'document_id' => $this->document->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Actions\DeleteDocumentAction;
use App\Actions\UploadDocumentAction;
use App\Enums\DocumentStatus;
use App\Http\Requests\StoreDocumentRequest;
use App\Jobs\ReprocessWithAiJob;
use App\Models\Document;
use App\Services\DocumentChunkStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function __construct(
        protected UploadDocumentAction $uploadDocument,
        protected DeleteDocumentAction $deleteDocument,
    ) {}

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $document = $this->uploadDocument->handle($request->user(), $request->file('file'));

        return response()->json([
            'id' => $document->id,
            'name' => $document->name,
            'status' => $document->status,
            'createdAt' => $document->created_at->diffForHumans(),
        ], 201);
    }

    public function preview(Document $document): JsonResponse
    {
        $text = $document->chunks()
            ->orderBy('chunk_index')
            ->pluck('content')
            ->implode("\n\n");

        return response()->json([
            'text' => $text,
            'extractionMethod' => $document->extraction_method,
            'ocrConfidence' => $document->ocr_confidence
                ? round($document->ocr_confidence, 1)
                : null,
        ]);
    }

    public function file(Document $document): BinaryFileResponse
    {
        return response()->file(Storage::disk('local')->path($document->path));
    }

    public function approve(Document $document, Request $request, DocumentChunkStorageService $chunkStorage): RedirectResponse
    {
        $text = trim($request->string('text')->toString());

        if ($text !== '') {
            $document->chunks()->delete();
            $chunkStorage->store($document, $text);
        }

        $document->update(['status' => DocumentStatus::Ready]);

        return back();
    }

    public function reprocess(Document $document): RedirectResponse
    {
        if (
            $document->ai_last_attempted_at &&
            $document->ai_last_attempted_at->diffInHours(now()) < 2
        ) {
            return back()->withErrors(['ai' => 'AI extraction was recently attempted. Please try again later.']);
        }

        $document->update([
            'status' => DocumentStatus::Processing,
            'status_message' => null,
            'ai_last_attempted_at' => now(),
        ]);

        ReprocessWithAiJob::dispatch($document);

        return back();
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->deleteDocument->handle($document);

        return back();
    }
}

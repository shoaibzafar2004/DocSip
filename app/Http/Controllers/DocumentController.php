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
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function file(Document $document): StreamedResponse
    {
        return Storage::disk('local')->response($document->path, $document->name);
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
        $document->update([
            'status' => DocumentStatus::Processing,
            'status_message' => null,
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

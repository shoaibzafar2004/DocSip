<?php

namespace App\Http\Controllers;

use App\Actions\DeleteDocumentAction;
use App\Actions\UploadDocumentAction;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

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

    public function destroy(Document $document): RedirectResponse
    {
        $this->deleteDocument->handle($document);

        return back();
    }
}

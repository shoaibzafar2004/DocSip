<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $path = $file->store('documents', 'local');

        $document = $request->user()->documents()->create([
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'status' => 'uploaded',
        ]);

        return response()->json([
            'id' => $document->id,
            'name' => $document->name,
            'status' => $document->status,
            'createdAt' => $document->created_at->diffForHumans(),
        ], 201);
    }

    public function destroy(Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);

        Storage::disk('local')->delete($document->path);
        $document->delete();

        return back();
    }
}

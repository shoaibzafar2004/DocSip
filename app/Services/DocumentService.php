<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    public function upload(User $user, UploadedFile $file): Document
    {
        $path = $file->store('documents', 'local');

        if ($path === false) {
            throw new \RuntimeException('Failed to store the uploaded file.');
        }

        return $user->documents()->create([
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'status' => 'uploaded',
        ]);
    }

    public function delete(Document $document): void
    {
        Storage::disk('local')->delete($document->path);
        $document->delete();
    }

    public function getForDashboard(User $user): Collection
    {
        return $user->documents()
            ->latest()
            ->get()
            ->map(fn (Document $document) => [
                'id' => $document->id,
                'name' => $document->name,
                'status' => $document->status,
                'statusMessage' => $document->status_message,
                'mimeType' => $document->mime_type,
                'aiLastAttemptedAt' => $document->ai_last_attempted_at?->toISOString(),
                'createdAt' => $document->created_at->diffForHumans(),
            ]);
    }

    public function getReadyDocuments(User $user): Collection
    {
        return $user->documents()
            ->where('status', DocumentStatus::Ready)
            ->get(['id', 'name']);
    }
}

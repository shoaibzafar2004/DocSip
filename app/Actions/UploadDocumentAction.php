<?php

namespace App\Actions;

use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Http\UploadedFile;

class UploadDocumentAction
{
    public function __construct(protected DocumentService $documentService) {}

    public function handle(User $user, UploadedFile $file): Document
    {
        $document = $this->documentService->upload($user, $file);
        ProcessDocumentJob::dispatch($document);

        return $document;
    }
}

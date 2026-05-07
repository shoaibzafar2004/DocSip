<?php

namespace App\Actions;

use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Support\Facades\Gate;

class DeleteDocumentAction
{
    public function __construct(protected DocumentService $documentService) {}

    public function handle(Document $document): void
    {
        Gate::authorize('delete', $document);
        $this->documentService->delete($document);
    }
}

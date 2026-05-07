<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Document $document)
    {
        //
    }

    public function handle(): void
    {
        $this->document->update(['status' => 'processing']);
        Log::info('Processing started', ['document_id' => $this->document->id]);
    }
}

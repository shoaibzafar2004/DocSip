<?php

namespace Tests\Feature;

use App\Actions\UploadDocumentAction;
use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UploadDocumentActionTest extends TestCase
{
    public function test_it_uploads_document_and_dispatches_processing_job(): void
    {
        Queue::fake();

        $user = User::factory()->make();
        $file = UploadedFile::fake()->create('report.pdf', 512, 'application/pdf');
        $document = new Document(['name' => 'report.pdf', 'status' => 'uploaded']);

        $service = $this->createMock(DocumentService::class);
        $service->expects($this->once())
            ->method('upload')
            ->with($user, $file)
            ->willReturn($document);

        $result = (new UploadDocumentAction($service))->handle($user, $file);

        Queue::assertPushed(ProcessDocumentJob::class, fn ($job) => $job->document === $document);
        $this->assertSame($document, $result);
    }
}

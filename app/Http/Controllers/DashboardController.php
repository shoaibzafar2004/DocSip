<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected DocumentService $documentService,
    ) {}

    public function index(Request $request): Response
    {
        $documents = $this->documentService->getForDashboard($request->user());

        $stats = [
            'total' => $documents->count(),
            'ready' => $documents->where('status', DocumentStatus::Ready)->count(),
            'pendingApproval' => $documents->where('status', DocumentStatus::PendingApproval)->count(),
            'processing' => $documents->where('status', DocumentStatus::Processing)->count(),
            'uploaded' => $documents->where('status', DocumentStatus::Uploaded)->count(),
            'failed' => $documents->where('status', DocumentStatus::Failed)->count(),
        ];

        return Inertia::render('dashboard', [
            'documents' => $documents,
            'stats' => $stats,
        ]);
    }
}

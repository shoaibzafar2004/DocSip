<?php

namespace App\Http\Controllers;

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
            'ready' => $documents->where('status', 'ready')->count(),
            'processing' => $documents->where('status', 'processing')->count(),
            'uploaded' => $documents->where('status', 'uploaded')->count(),
        ];

        return Inertia::render('dashboard', [
            'documents' => $documents,
            'stats' => $stats,
        ]);
    }
}

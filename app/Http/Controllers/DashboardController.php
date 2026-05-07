<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $documents = $request->user()
            ->documents()
            ->latest()
            ->get()
            ->map(fn ($doc) => [
                'id' => $doc->id,
                'name' => $doc->name,
                'status' => $doc->status,
                'createdAt' => $doc->created_at->diffForHumans(),
            ]);

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

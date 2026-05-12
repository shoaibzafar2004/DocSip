<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    Route::get('/chat', [ConversationController::class, 'index'])->name('conversations');
    Route::post('/chat', [ConversationController::class, 'store'])->name('conversations.store');
    Route::get('/chat/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::post('/chat/{conversation}/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::delete('/chat/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy');
});

require __DIR__.'/settings.php';

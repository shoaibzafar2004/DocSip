<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use App\Services\AnswerService;
use App\Services\DocumentQueryService;
use App\Services\EmbeddingService;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    public function __construct(
        protected DocumentQueryService $documentQueryService,
        protected EmbeddingService $embeddingService,
        protected AnswerService $answerService
    ) {}

    public function store(StoreMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);
        $embedding = $this->embeddingService->embedMany([$request->content]);
        $message = $conversation->messages()->create([
            'content' => $request->content,
            'role' => 'user',
            'embedding' => $embedding[0],
        ]);
        $context = $this->documentQueryService->query($message);

        $answer = $this->answerService->answer($message, $context);

        $assistantMessage = $conversation->messages()->create([
            'content' => $answer,
            'role' => 'assistant',
        ]);

        return response()->json([
            'userMessage' => $message,
            'assistantMessage' => $assistantMessage,
        ], 201);
    }
}

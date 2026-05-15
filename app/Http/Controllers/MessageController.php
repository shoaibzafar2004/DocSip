<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AnswerService;
use App\Services\ConversationTitleService;
use App\Services\DocumentQueryService;
use App\Services\EmbeddingService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageController extends Controller
{
    public function __construct(
        protected DocumentQueryService $documentQueryService,
        protected EmbeddingService $embeddingService,
        protected AnswerService $answerService,
        protected ConversationTitleService $titleService,
    ) {}

    public function store(StoreMessageRequest $request, Conversation $conversation): StreamedResponse
    {
        $this->authorize('view', $conversation);

        if ($conversation->documents()->doesntExist()) {
            abort(403, 'This conversation is locked because all documents have been deleted.');
        }

        $embedding = $this->embeddingService->embedMany([$request->content]);
        $message = $conversation->messages()->create([
            'content' => $request->content,
            'role' => 'user',
            'embedding' => $embedding[0],
        ]);

        if ($conversation->messages()->count() === 1 && ! $conversation->title) {
            $conversation->update(['title' => $this->titleService->generate($message->content)]);
        }

        $context = $this->documentQueryService->query($message);

        return response()->stream(function () use ($conversation, $message, $context) {
            $this->sendEvent([
                'type' => 'user',
                'message' => $this->serializeMessage($message),
                'title' => $conversation->fresh()->title,
            ]);

            $fullContent = '';

            foreach ($this->answerService->stream($message, $context) as $chunk) {
                $fullContent .= $chunk;
                $this->sendEvent(['type' => 'chunk', 'content' => $chunk]);
            }

            $assistantMessage = $conversation->messages()->create([
                'content' => $fullContent,
                'role' => 'assistant',
            ]);

            $this->sendEvent([
                'type' => 'done',
                'message' => $this->serializeMessage($assistantMessage),
            ]);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function sendEvent(array $data): void
    {
        echo 'data: '.json_encode($data)."\n\n";
        ob_flush();
        flush();
    }

    private function serializeMessage(Message $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'createdAt' => $message->created_at->diffForHumans(),
        ];
    }
}

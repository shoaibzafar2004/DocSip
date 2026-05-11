<?php

namespace App\Http\Controllers;

use App\Actions\CreateConversationAction;
use App\Http\Requests\StoreConversationRequest;
use App\Models\Conversation;
use App\Services\ConversationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    public function __construct(
        protected CreateConversationAction $createConversation,
        protected ConversationService $conversationService
    ) {}

    public function index(): Response
    {
        $user = auth()->user();

        return Inertia::render('chat', [
            'conversations' => $this->conversationService->getForSidebar($user),
        ]);
    }

    public function store(StoreConversationRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $documentIds = $request->document_ids;
        $documents = $user->documents()
            ->whereIn('id', $documentIds)
            ->where('status', 'ready')
            ->get();

        if ($documents->count() !== count($documentIds)) {
            abort(422, 'One or more documents are invalid or not ready.');
        }

        $conversation = $this->createConversation->handle($user, $documentIds);

        return redirect()->route('conversations.show', $conversation);
    }

    public function show(Conversation $conversation): Response
    {
        $this->authorize('view', $conversation);
        $conversation = $this->conversationService->getWithMessages($conversation);

        return Inertia::render('chat/show', [
            'conversation' => $conversation,
            'messages' => $conversation->messages,
            'attachedDocuments' => $conversation->documents,
        ]);
    }
}

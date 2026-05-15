<?php

namespace App\Http\Controllers;

use App\Actions\CreateConversationAction;
use App\Http\Requests\StoreConversationRequest;
use App\Models\Conversation;
use App\Services\ConversationService;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    public function __construct(
        protected CreateConversationAction $createConversation,
        protected ConversationService $conversationService,
        protected DocumentService $documentService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('chat', [
            'conversations' => $this->conversationService->getForSidebar($user),
            'readyDocuments' => $this->documentService->getReadyDocuments($user),
        ]);
    }

    public function store(StoreConversationRequest $request): RedirectResponse
    {
        $user = $request->user();
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

    public function show(Conversation $conversation, Request $request): Response
    {
        $this->authorize('view', $conversation);
        $conversation = $this->conversationService->getWithMessages($conversation);

        return Inertia::render('chat/show', [
            'conversation' => $conversation,
            'messages' => $conversation->messages,
            'attachedDocuments' => $conversation->documents,
            'isLocked' => $conversation->documents->isEmpty(),
            'readyDocuments' => $this->documentService->getReadyDocuments($request->user()),
        ]);
    }

    public function destroy(Conversation $conversation): RedirectResponse
    {
        $this->authorize('delete', $conversation);
        $conversation->delete();

        return redirect()->route('conversations');
    }
}

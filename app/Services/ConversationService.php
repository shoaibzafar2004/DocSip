<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Collection;

class ConversationService
{
    public function getForSidebar(User $user): Collection
    {
        return $user->conversations()->latest()->take(20)->get();
    }

    public function getWithMessages(Conversation $conversation): Conversation
    {
        return $conversation->load([
            'documents',
            'messages' => fn ($q) => $q->oldest(),
        ]);
    }
}

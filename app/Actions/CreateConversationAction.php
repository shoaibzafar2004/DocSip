<?php

namespace App\Actions;

use App\Models\Conversation;
use App\Models\User;

class CreateConversationAction
{
    public function handle(User $user, array $documentIds): Conversation
    {
        $conversation = $user->conversations()->create(['title' => null]);
        $conversation->documents()->attach($documentIds);

        return $conversation;
    }
}

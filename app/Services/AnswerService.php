<?php

namespace App\Services;

use App\Models\Message;
use Gemini\Laravel\Facades\Gemini;

class AnswerService
{
    public function answer(Message $message, array $context): string
    {
        $history = $message->conversation
            ->messages()
            ->where('id', '!=', $message->id)
            ->oldest()
            ->get()
            ->map(fn ($m) => ucfirst($m->role).': '.$m->content)
            ->join("\n");

        $prompt = implode("\n\n", [
            'You are a helpful assistant. Answer the user question using ONLY the provided context. If the answer is not in the context, say so.',
            'Context:'."\n".implode("\n\n", $context),
            'Conversation history:'."\n".$history,
            'User question: '.$message->content,
        ]);

        return $this->callGemini($prompt);
    }

    protected function callGemini(string $prompt): string
    {
        return Gemini::generativeModel(model: 'models/gemini-2.5-flash')->generateContent($prompt)->text();
    }
}

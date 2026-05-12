<?php

namespace App\Services;

use Gemini\Laravel\Facades\Gemini;

class ConversationTitleService
{
    public function generate(string $firstMessage): string
    {
        $prompt = implode("\n\n", [
            'Generate a concise 4-6 word title for a conversation that starts with this message. Return only the title, no punctuation or quotes.',
            'Message: '.$firstMessage,
        ]);

        return Gemini::generativeModel(model: 'models/gemini-2.5-flash')->generateContent($prompt)->text();
    }
}

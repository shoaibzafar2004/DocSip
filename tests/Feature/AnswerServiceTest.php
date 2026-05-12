<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\AnswerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnswerServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(?string $fixedAnswer = 'The answer is 42.'): AnswerService
    {
        return new class($fixedAnswer) extends AnswerService
        {
            public function __construct(private string $fixedAnswer) {}

            protected function callGemini(string $prompt): string
            {
                return $this->fixedAnswer;
            }
        };
    }

    private function makeCaptureService(string &$captured): AnswerService
    {
        return new class($captured) extends AnswerService
        {
            public function __construct(private string &$captured) {}

            protected function callGemini(string $prompt): string
            {
                $this->captured = $prompt;

                return 'answer';
            }
        };
    }

    public function test_returns_answer_string_from_gemini(): void
    {
        $conversation = Conversation::factory()->create();
        $message = Message::factory()->for($conversation)->create([
            'role' => 'user',
            'content' => 'What is the answer?',
        ]);

        $result = $this->makeService()->answer($message, ['Some context here.']);

        $this->assertSame('The answer is 42.', $result);
    }

    public function test_history_excludes_current_message(): void
    {
        $capturedPrompt = '';
        $service = $this->makeCaptureService($capturedPrompt);

        $conversation = Conversation::factory()->create();
        Message::factory()->for($conversation)->create([
            'role' => 'user',
            'content' => 'Previous question',
            'created_at' => now()->subMinute(),
        ]);
        $current = Message::factory()->for($conversation)->create([
            'role' => 'user',
            'content' => 'Current question',
        ]);

        $service->answer($current, []);

        $this->assertStringContainsString('User: Previous question', $capturedPrompt);
        $this->assertStringNotContainsString('User: Current question', $capturedPrompt);
    }

    public function test_history_labels_user_and_assistant_roles(): void
    {
        $capturedPrompt = '';
        $service = $this->makeCaptureService($capturedPrompt);

        $conversation = Conversation::factory()->create();
        Message::factory()->for($conversation)->create([
            'role' => 'user',
            'content' => 'Hello',
            'created_at' => now()->subMinutes(2),
        ]);
        Message::factory()->for($conversation)->create([
            'role' => 'assistant',
            'content' => 'Hi there',
            'created_at' => now()->subMinute(),
        ]);
        $current = Message::factory()->for($conversation)->create([
            'role' => 'user',
            'content' => 'Follow up',
        ]);

        $service->answer($current, []);

        $this->assertStringContainsString('User: Hello', $capturedPrompt);
        $this->assertStringContainsString('Assistant: Hi there', $capturedPrompt);
    }

    public function test_prompt_includes_context(): void
    {
        $capturedPrompt = '';
        $service = $this->makeCaptureService($capturedPrompt);

        $conversation = Conversation::factory()->create();
        $message = Message::factory()->for($conversation)->create(['role' => 'user']);

        $service->answer($message, ['Chunk one content.', 'Chunk two content.']);

        $this->assertStringContainsString('Chunk one content.', $capturedPrompt);
        $this->assertStringContainsString('Chunk two content.', $capturedPrompt);
    }
}

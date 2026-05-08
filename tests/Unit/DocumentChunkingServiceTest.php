<?php

namespace Tests\Unit;

use App\Services\DocumentChunkingService;
use Tests\TestCase;

class DocumentChunkingServiceTest extends TestCase
{
    private DocumentChunkingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DocumentChunkingService;
    }

    public function test_empty_string_returns_empty_array(): void
    {
        $this->assertSame([], $this->service->chunk(''));
    }

    public function test_short_text_returns_single_chunk(): void
    {
        $text = str_repeat('word ', 50); // 250 chars, well under 1000

        $chunks = $this->service->chunk($text);

        $this->assertCount(1, $chunks);
        $this->assertSame(trim($text), $chunks[0]);
    }

    public function test_long_text_produces_multiple_chunks(): void
    {
        $text = str_repeat('word ', 300); // 1500 chars

        $chunks = $this->service->chunk($text);

        $this->assertGreaterThan(1, count($chunks));
    }

    public function test_no_chunk_exceeds_chunk_size(): void
    {
        $text = str_repeat('word ', 500); // 2500 chars

        $chunks = $this->service->chunk($text);

        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(1000, strlen($chunk));
        }
    }

    public function test_consecutive_chunks_overlap(): void
    {
        $words = array_map(fn (int $i): string => "word{$i}", range(1, 400));
        $text = implode(' ', $words);

        $chunks = $this->service->chunk($text);

        $this->assertGreaterThanOrEqual(2, count($chunks));

        $lastWordsOfFirstChunk = array_slice(explode(' ', $chunks[0]), -10);
        $overlapFound = false;

        foreach ($lastWordsOfFirstChunk as $word) {
            if (str_contains($chunks[1], $word)) {
                $overlapFound = true;
                break;
            }
        }

        $this->assertTrue($overlapFound, 'No overlap found between consecutive chunks.');
    }

    public function test_chunks_do_not_cut_mid_word(): void
    {
        $text = str_repeat('longword ', 200); // 1800 chars

        $chunks = $this->service->chunk($text);

        foreach ($chunks as $chunk) {
            $this->assertStringStartsNotWith('ongword', $chunk);
            $this->assertDoesNotMatchRegularExpression('/longwor$/', $chunk);
        }
    }

    public function test_all_words_appear_in_at_least_one_chunk(): void
    {
        $words = array_map(fn (int $i): string => "unique{$i}", range(1, 300));
        $text = implode(' ', $words);

        $chunks = $this->service->chunk($text);
        $combined = implode(' ', $chunks);

        foreach (array_filter($words, fn (int $i): bool => $i % 50 === 0, ARRAY_FILTER_USE_KEY) as $word) {
            $this->assertStringContainsString($word, $combined);
        }
    }
}

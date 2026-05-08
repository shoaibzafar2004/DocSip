<?php

namespace App\Services;

class DocumentChunkingService
{
    public function chunk(string $text): array
    {
        $chunkSize = 1000;
        $overlap = 200;
        $chunks = [];
        $offset = 0;
        $textLength = strlen($text);

        while ($offset < $textLength) {
            $end = min($offset + $chunkSize, $textLength);

            if ($end < $textLength) {
                $lastSpace = strrpos(substr($text, $offset, $chunkSize), ' ');
                if ($lastSpace !== false) {
                    $end = $offset + $lastSpace;
                }
            }
            $chunks[] = trim(substr($text, $offset, $end - $offset));

            if ($end >= $textLength) {
                break;
            }

            $nextOffset = $end - $overlap;
            $firstSpace = strpos($text, ' ', max(0, $nextOffset));
            $offset = ($firstSpace !== false && $firstSpace > $offset) ? $firstSpace + 1 : $end;
        }

        return $chunks;
    }
}

<?php

namespace App\Services;

use App\Models\Document;
use Gemini\Data\Blob;
use Gemini\Enums\MimeType;
use Gemini\Laravel\Facades\Gemini;

class GeminiOcrService
{
    public function extract(Document $document, string $filePath): array
    {
        $mimeType = MimeType::from($document->mime_type);
        $data = base64_encode(file_get_contents($filePath));

        $text = Gemini::generativeModel(model: 'models/gemini-2.5-flash')
            ->generateContent([
                new Blob(mimeType: $mimeType, data: $data),
                'Extract all text from this document exactly as it appears. Preserve line breaks and structure. Return only the extracted text with no commentary.',
            ])
            ->text();

        return [
            'text' => $text,
            'extraction_method' => 'gemini',
            'confidence' => null,
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Log;

class ExtractionDispatcherService
{
    public function __construct(
        public PdfExtractionService $pdfExtractor,
        public OcrExtractionService $ocrExtractor,
    ) {}

    /**
     * @return array{text: string, extraction_method: string, confidence: float|null}
     */
    public function extract(Document $document, string $path): array
    {
        $mimeType = $document->mime_type;

        if (str_starts_with($mimeType, 'image/')) {
            return $this->extractWithOcr($document, $path);
        }

        if ($mimeType === 'application/pdf') {
            return $this->extractFromPdf($document, $path);
        }

        if ($mimeType === 'text/plain') {
            return $this->extractFromText($path);
        }

        throw new \RuntimeException("Unsupported file type: {$mimeType}");
    }

    private function extractFromPdf(Document $document, string $path): array
    {
        try {
            $text = $this->pdfExtractor->extract($path);

            if (strlen(trim($text)) > 50) {
                return ['text' => $text, 'extraction_method' => 'pdftotext', 'confidence' => null];
            }
        } catch (\Exception $e) {
            Log::warning('pdftotext failed, falling back to OCR', ['document_id' => $document->id]);
        }

        return $this->extractWithOcr($document, $path);
    }

    private function extractWithOcr(Document $document, string $path): array
    {
        $result = $this->ocrExtractor->extract($document, $path);

        return [
            'text' => $result['text'],
            'extraction_method' => 'tesseract',
            'confidence' => $result['confidence'],
        ];
    }

    private function extractFromText(string $path): array
    {
        return [
            'text' => file_get_contents($path),
            'extraction_method' => 'plaintext',
            'confidence' => null,
        ];
    }
}

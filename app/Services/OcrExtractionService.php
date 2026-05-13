<?php

namespace App\Services;

use App\Models\Document;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrExtractionService
{
    public function extract(Document $document, string $filePath): array
    {
        $tempDir = null;

        try {
            if ($document->mime_type === 'application/pdf') {
                $tempDir = $this->pdfToImages($filePath);
                $imagePaths = glob("{$tempDir}/page-*.png");
            } else {
                $imagePaths = [$filePath];
            }

            $texts = [];
            $confidences = [];

            foreach ($imagePaths as $imagePath) {
                $texts[] = (new TesseractOCR($imagePath))->lang('eng')->run();
                $confidences[] = $this->getConfidence($imagePath);
            }

            return [
                'text' => implode("\n\n", array_filter($texts)),
                'confidence' => count($confidences) > 0
                    ? array_sum($confidences) / count($confidences)
                    : 0.0,
            ];
        } finally {
            if ($tempDir !== null) {
                $this->cleanupTempDir($tempDir);
            }
        }
    }

    private function pdfToImages(string $pdfPath): string
    {
        $tempDir = sys_get_temp_dir().'/ocr_'.uniqid();
        mkdir($tempDir, 0755, true);

        exec(sprintf(
            'pdftoppm -png -r 300 %s %s/page',
            escapeshellarg($pdfPath),
            escapeshellarg($tempDir)
        ));

        return $tempDir;
    }

    private function getConfidence(string $imagePath): float
    {
        exec(sprintf('tesseract %s stdout tsv 2>/dev/null', escapeshellarg($imagePath)), $output);

        $confidences = [];

        foreach (array_slice($output, 1) as $line) {
            $columns = explode("\t", $line);
            if (isset($columns[10]) && (float) $columns[10] >= 0) {
                $confidences[] = (float) $columns[10];
            }
        }

        return count($confidences) > 0
            ? array_sum($confidences) / count($confidences)
            : 0.0;
    }

    private function cleanupTempDir(string $dir): void
    {
        foreach (glob("{$dir}/*") as $file) {
            unlink($file);
        }

        rmdir($dir);
    }
}

<?php

namespace App\Services;

use Spatie\PdfToText\Pdf;

class PdfExtractionService
{
    public function extract(string $path): string
    {
        return Pdf::getText($path);
    }
}

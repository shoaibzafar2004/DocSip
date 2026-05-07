<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:30720', 'mimes:pdf,doc,docx,txt,jpg,jpeg,png,csv,xlsx,xls'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.max' => 'The selected file may not be greater than 30MB.',
            'file.mimes' => 'The selected file must be a PDF, Word document, text file, image, CSV, or Excel file.',
        ];
    }
}

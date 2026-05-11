<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer', 'exists:documents,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_ids.array' => 'The document IDs must be an array.',
            'document_ids.*.exists' => 'One or more selected documents do not exist.',
        ];
    }
}

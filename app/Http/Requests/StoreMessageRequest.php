<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Please enter a message.',
            'content.string' => 'The message must be a valid string.',
            'content.min' => 'The message can not be empty.',
        ];
    }
}

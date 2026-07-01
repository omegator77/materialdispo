<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttachItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_id' => ['required'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

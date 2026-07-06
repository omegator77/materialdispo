<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MailingListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
            'recipient_name' => ['nullable', 'array'],
            'recipient_name.*' => ['nullable', 'string', 'max:255'],
            'recipient_email' => ['nullable', 'array'],
            'recipient_email.*' => ['nullable', 'email', 'max:255'],
        ];
    }
}

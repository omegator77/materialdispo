<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bezeichnung' => ['required'],
            'kontakt' => ['nullable'],
            'phone' => ['nullable'],
            'email' => ['nullable', 'email'],
        ];
    }
}

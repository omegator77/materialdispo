<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductionImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['nullable', 'array'],
            'items.*' => ['array'],
            'items.*.action' => ['required', 'in:keep,replace,skip'],
            'items.*.replacement_id' => ['nullable', 'integer', 'exists:items,id'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
            'configs' => ['nullable', 'array'],
            'configs.*' => ['in:import,skip'],
        ];
    }
}

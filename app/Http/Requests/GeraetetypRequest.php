<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GeraetetypRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'units_id' => ['required', 'exists:units,id'],
            'bezeichnung' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bezeichnung' => ['required'],
            'booking_start' => ['required', 'date_format:d.m.Y'],
            'booking_end' => ['required', 'date_format:d.m.Y', 'after_or_equal:booking_start'],
            'packlist_notes' => ['nullable', 'string'],
        ];
    }
}

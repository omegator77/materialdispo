<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MietvorgangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $requiredOnCreate = $this->isMethod('post') ? ['required'] : ['nullable'];

        return [
            'bezeichnung' => ['nullable', 'string', 'max:255'],
            'suppliers_id' => [...$requiredOnCreate, 'exists:suppliers,id'],
            'rent_start' => [...$requiredOnCreate, 'date_format:d.m.Y'],
            'rent_end' => [...$requiredOnCreate, 'date_format:d.m.Y', 'after_or_equal:rent_start'],
            'transport_type_start' => ['nullable', 'string', 'max:255'],
            'transport_type_end' => ['nullable', 'string', 'max:255'],
            'notify_supplier' => ['nullable', 'boolean'],
            'reminder_days_before_start' => ['nullable', 'integer', 'min:0', 'max:60'],
            'reminder_days_before_end' => ['nullable', 'integer', 'min:0', 'max:60'],
            'mailing_list_id' => ['nullable', 'exists:mailing_lists,id'],
        ];
    }
}

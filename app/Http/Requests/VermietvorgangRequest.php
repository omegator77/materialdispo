<?php

namespace App\Http\Requests;

use App\Models\Vermietvorgang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VermietvorgangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $requiredOnCreate = $this->isMethod('post') ? ['required'] : ['nullable'];

        return [
            'mieter_id' => [...$requiredOnCreate, 'exists:mieter,id'],
            'rent_start' => [...$requiredOnCreate, 'date_format:d.m.Y'],
            'rent_end' => [...$requiredOnCreate, 'date_format:d.m.Y', 'after_or_equal:rent_start'],
            'transport_type_start' => ['nullable', Rule::in(array_keys(Vermietvorgang::TRANSPORT_TYPES_START))],
            'transport_type_end' => ['nullable', Rule::in(array_keys(Vermietvorgang::TRANSPORT_TYPES_END))],
            'notify_mieter' => ['nullable', 'boolean'],
            'reminder_days_before_start' => ['nullable', 'integer', 'min:0', 'max:60'],
            'reminder_days_before_end' => ['nullable', 'integer', 'min:0', 'max:60'],
            'mailing_list_id' => ['nullable', 'exists:mailing_lists,id'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rows' => ['required', 'array', 'min:1', 'max:50'],
            'rows.*.geraetetyp_id' => ['nullable', 'exists:geraetetypen,id'],
            'rows.*.units_id' => ['required', 'exists:units,id'],
            'rows.*.bezeichnung' => ['nullable', 'string', 'max:255'],
            'rows.*.anzahl' => ['nullable', 'integer', 'min:1', 'max:100'],
            'rows.*.nummern' => ['nullable', 'string', 'max:500'],
            'rows.*.nummer_start' => ['nullable', 'string', 'max:50'],
            'rows.*.suppliers_id' => ['nullable', 'exists:suppliers,id'],
            'rows.*.rent_start' => ['nullable', 'required_with:rows.*.suppliers_id', 'date_format:d.m.Y'],
            'rows.*.rent_end' => ['nullable', 'required_with:rows.*.suppliers_id', 'date_format:d.m.Y', 'after_or_equal:rows.*.rent_start'],
            'rows.*.description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rows.*.units_id.required' => 'Bitte in jeder Zeile eine Gruppe wählen.',
            'rows.*.rent_start.required_with' => 'Bei Mietmaterial werden Mietbeginn und -ende benötigt.',
            'rows.*.rent_end.required_with' => 'Bei Mietmaterial werden Mietbeginn und -ende benötigt.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Bestellung;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BestellungRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['versanddienstleister', 'sendungsnummer'] as $feld) {
            if (is_string($this->input($feld))) {
                $wert = trim($this->input($feld));
                $this->merge([$feld => $wert === '' ? null : $wert]);
            }
        }

        if (is_string($this->input('angekommen_notiz'))) {
            $wert = trim(preg_replace('/\s+/u', ' ', $this->input('angekommen_notiz')));
            $this->merge(['angekommen_notiz' => $wert === '' ? null : $wert]);
        }
    }

    public function rules(): array
    {
        return [
            'bestellt_bei' => ['required', 'string', 'max:255'],
            'bestellt_was' => ['required', 'string', 'max:2000'],
            'bezahlt_mit' => ['required', 'string', 'max:255'],
            'versanddienstleister' => ['nullable', Rule::in(array_keys(Bestellung::CARRIERS))],
            'sendungsnummer' => ['nullable', 'string', 'max:100'],
            'angekommen_notiz' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'bestellt_bei' => 'Bestellt bei',
            'bestellt_was' => 'Bestellt was',
            'bezahlt_mit' => 'Bezahlt mit',
            'versanddienstleister' => 'Versanddienstleister',
            'sendungsnummer' => 'Sendungsnummer',
            'angekommen_notiz' => 'Ankunftsnotiz',
        ];
    }
}

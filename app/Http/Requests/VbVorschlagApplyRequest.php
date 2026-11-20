<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VbVorschlagApplyRequest extends FormRequest
{
    private const SLOTS = ['camera', 'lens', 'tripod', 'tripod_head', 'large_lens_adapter'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = ['nullable', 'integer', 'exists:items,id'];

        return [
            'rigs' => ['nullable', 'array'],
            'rigs.*' => ['array'],
            'rigs.*.take' => ['nullable', 'boolean'],
            'rigs.*.camera' => $id,
            'rigs.*.lens' => $id,
            'rigs.*.tripod' => $id,
            'rigs.*.tripod_head' => $id,
            'rigs.*.large_lens_adapter' => $id,
            'items' => ['nullable', 'array'],
            'items.*' => $id,
        ];
    }

    /** Leere Auswahlfelder ("— leer —") werden zu null, leere Material-Einträge entfallen. */
    protected function prepareForValidation(): void
    {
        $rigs = collect($this->input('rigs', []))->filter(fn ($r) => is_array($r))->map(function (array $rig) {
            foreach (self::SLOTS as $slot) {
                $rig[$slot] = ($rig[$slot] ?? '') === '' ? null : $rig[$slot];
            }

            return $rig;
        })->all();

        $items = collect($this->input('items', []))->filter(fn ($v) => $v !== null && $v !== '')->values()->all();

        $this->merge(['rigs' => $rigs, 'items' => $items]);
    }
}

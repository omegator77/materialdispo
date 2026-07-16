<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VbProtokollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kunde' => ['nullable', 'string', 'max:255'],
            'produktionsort' => ['nullable', 'string', 'max:255'],

            'crew_ul' => ['nullable', 'string', 'max:255'],
            'crew_bt_sng' => ['nullable', 'string', 'max:255'],
            'crew_ti' => ['nullable', 'string', 'max:255'],
            'crew_sng' => ['nullable', 'string', 'max:255'],
            'crew_bt_dl' => ['nullable', 'string', 'max:255'],
            'crew_tt' => ['nullable', 'string', 'max:255'],
            'crew_tl' => ['nullable', 'string', 'max:255'],
            'crew_ba' => ['nullable', 'string', 'max:255'],
            'crew_ta' => ['nullable', 'string', 'max:255'],
            'crew_kabelhilfen' => ['nullable', 'string', 'max:255'],
            'crew_kamera' => ['nullable', 'string', 'max:255'],
            'crew_evs' => ['nullable', 'string', 'max:255'],

            'anforderungen' => ['nullable', 'array'],
            'anforderungen.*.mode' => ['nullable', 'string', 'in:typ,kamera'],
            'anforderungen.*.unit_id' => ['nullable', 'exists:units,id'],
            'anforderungen.*.geraetetyp_id' => ['nullable', 'exists:geraetetypen,id'],
            'anforderungen.*.anzahl' => ['nullable', 'integer', 'min:1'],
            'anforderungen.*.notiz' => ['nullable', 'string', 'max:255'],
            'anforderungen.*.cam_number' => ['nullable', 'string', 'max:255'],
            'anforderungen.*.lens_geraetetyp_id' => ['nullable', 'exists:geraetetypen,id'],
            'anforderungen.*.tripod_geraetetyp_id' => ['nullable', 'exists:geraetetypen,id'],
            'anforderungen.*.tripod_head_geraetetyp_id' => ['nullable', 'exists:geraetetypen,id'],
            'anforderungen.*.adapter_geraetetyp_id' => ['nullable', 'exists:geraetetypen,id'],

            'freitext_bloecke' => ['nullable', 'array'],
            'freitext_bloecke.*.ueberschrift' => ['nullable', 'string', 'max:255'],
            'freitext_bloecke.*.text' => ['nullable', 'string'],

            'fotos' => ['nullable', 'array'],
            'fotos.*' => ['nullable', 'image', 'max:8192'],
        ];
    }

    /**
     * Felder ohne 'anforderungen', 'freitext_bloecke' und 'fotos' — direkt für VbProtokoll::create/update.
     */
    public function fields(): array
    {
        return collect($this->validated())->except(['anforderungen', 'freitext_bloecke', 'fotos'])->all();
    }

    public function anforderungenInput(): array
    {
        return $this->validated('anforderungen') ?? [];
    }

    public function freitextBloeckeInput(): array
    {
        return $this->validated('freitext_bloecke') ?? [];
    }
}

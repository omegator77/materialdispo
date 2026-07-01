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

            'besonderheiten' => ['nullable', 'string'],
            'kabelwege' => ['nullable', 'string'],
            'audio_mic' => ['nullable', 'string'],
            'audio_inear' => ['nullable', 'string'],
            'audio_kommplatz' => ['nullable', 'string'],
            'isdn_funk' => ['nullable', 'string'],
            'maz_evs_usb' => ['nullable', 'string'],
            'monitore' => ['nullable', 'string'],
            'sonstiges' => ['nullable', 'string'],
            'zeitplan' => ['nullable', 'string'],

            'anforderungen' => ['nullable', 'array'],
            'anforderungen.*.mode' => ['nullable', 'string', 'in:typ,frei,kamera'],
            'anforderungen.*.unit_id' => ['nullable', 'exists:units,id'],
            'anforderungen.*.geraetetyp_id' => ['nullable', 'exists:geraetetypen,id'],
            'anforderungen.*.freitext' => ['nullable', 'string', 'max:255'],
            'anforderungen.*.anzahl' => ['nullable', 'integer', 'min:1'],
            'anforderungen.*.notiz' => ['nullable', 'string', 'max:255'],
            'anforderungen.*.cam_number' => ['nullable', 'string', 'max:255'],
            'anforderungen.*.lens_geraetetyp_id' => ['nullable', 'exists:geraetetypen,id'],
            'anforderungen.*.tripod_geraetetyp_id' => ['nullable', 'exists:geraetetypen,id'],
            'anforderungen.*.tripod_head_geraetetyp_id' => ['nullable', 'exists:geraetetypen,id'],
            'anforderungen.*.adapter_geraetetyp_id' => ['nullable', 'exists:geraetetypen,id'],

            'fotos' => ['nullable', 'array'],
            'fotos.*' => ['nullable', 'image', 'max:8192'],
        ];
    }

    /**
     * Felder ohne 'anforderungen' und 'fotos' — direkt für VbProtokoll::create/update.
     */
    public function fields(): array
    {
        return collect($this->validated())->except(['anforderungen', 'fotos'])->all();
    }

    public function anforderungenInput(): array
    {
        return $this->validated('anforderungen') ?? [];
    }
}

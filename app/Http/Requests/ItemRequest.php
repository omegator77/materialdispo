<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Grunddaten
            |--------------------------------------------------------------------------
            */
            'units_id' => ['required', 'exists:units,id'],
            'geraetetyp_id' => ['nullable', 'exists:geraetetypen,id'],
            'suppliers_id' => ['nullable', 'exists:suppliers,id'],
            'mieter_id' => ['nullable', 'exists:mieter,id'],
            'bezeichnung' => ['required', 'string', 'max:255'],
            'nummer' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            /*
            |--------------------------------------------------------------------------
            | Mietdaten
            |--------------------------------------------------------------------------
            | Ein Item ist Mietmaterial, sobald ein Vermieter gesetzt ist.
            | Ohne Vermieter werden rent_start und rent_end später auf null gesetzt.
            */
            'rent_start' => $this->filled('suppliers_id')
                ? ['required', 'date_format:d.m.Y']
                : ['nullable'],

            'rent_end' => $this->filled('suppliers_id')
                ? ['required', 'date_format:d.m.Y', 'after_or_equal:rent_start']
                : ['nullable'],

            /*
            |--------------------------------------------------------------------------
            | Verleihdaten
            |--------------------------------------------------------------------------
            | Ein Item wird an einen Mieter verliehen, sobald ein Mieter gesetzt ist.
            | Ohne Mieter werden verleih_start und verleih_end später auf null gesetzt.
            */
            'verleih_start' => $this->filled('mieter_id')
                ? ['required', 'date_format:d.m.Y']
                : ['nullable'],

            'verleih_end' => $this->filled('mieter_id')
                ? ['required', 'date_format:d.m.Y', 'after_or_equal:verleih_start']
                : ['nullable'],

            /*
            |--------------------------------------------------------------------------
            | Kamera-Metadaten
            |--------------------------------------------------------------------------
            | Werden nur bei Unit "Kameras" in camera_details gespeichert.
            */
            'body_serial' => ['nullable', 'string', 'max:255'],
            'fiber_adapter_serial' => ['nullable', 'string', 'max:255'],

            'large_viewfinder_model' => ['nullable', 'string', 'max:255'],
            'large_viewfinder_type' => ['nullable', 'in:OLED,LCD'],
            'large_viewfinder_serial' => ['nullable', 'string', 'max:255'],

            'small_viewfinder_model' => ['nullable', 'string', 'max:255'],
            'small_viewfinder_type' => ['nullable', 'in:OLED,LCD'],
            'small_viewfinder_serial' => ['nullable', 'string', 'max:255'],

            'ssl_license' => ['nullable', 'boolean'],

            /*
            |--------------------------------------------------------------------------
            | Monitor-Metadaten
            |--------------------------------------------------------------------------
            | Werden nur bei den beiden Monitor-Units in monitor_details gespeichert.
            */
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'screen_size' => ['nullable', 'string', 'max:50'],

            'has_speakers' => ['nullable', 'boolean'],
            'has_headphone' => ['nullable', 'boolean'],

            'converter_number' => ['nullable', 'string', 'max:50'],
            'converter_model' => ['nullable', 'string', 'max:255'],
            'converter_audio' => ['nullable', 'boolean'],

            'max_input_format' => ['nullable', 'string', 'max:255'],

            'has_stand' => ['nullable', 'boolean'],
            'stand_number' => ['nullable', 'string', 'max:50'],

            /* Objektiv-Metadaten */
            'lens_manufacturer' => ['nullable', 'string', 'max:255'],
            'lens_model' => ['nullable', 'string', 'max:255'],
            'lens_serial_number' => ['nullable', 'string', 'max:255'],
            'lens_zoom_factor' => ['nullable', 'string', 'max:50'],
            'lens_zoom_servo_model' => ['nullable', 'string', 'max:255'],
            'lens_zoom_servo_serial_number' => ['nullable', 'string', 'max:255'],
            'lens_focus_servo_model' => ['nullable', 'string', 'max:255'],
            'lens_focus_servo_serial_number' => ['nullable', 'string', 'max:255'],
        ];
    }
}

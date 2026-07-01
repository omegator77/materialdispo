<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CameraConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cam_number' => ['required', 'string', 'max:255'],
            'camera' => ['required', 'exists:items,id'],
            'lens' => ['nullable', 'exists:items,id'],
            'tripod' => ['nullable', 'exists:items,id'],
            'tripod_head' => ['nullable', 'exists:items,id'],
            'large_lens_adapter' => ['nullable', 'exists:items,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

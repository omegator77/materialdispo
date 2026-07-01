<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Unit;
use Illuminate\Http\Request;

class ItemDetailSyncService
{
    public function syncCameraDetails(Request $request, Item $item): void
    {
        if ((int) $request->units_id === 1) {
            $item->cameraDetail()->updateOrCreate(
                ['item_id' => $item->id],
                [
                    'body_serial' => $request->body_serial,
                    'fiber_adapter_serial' => $request->fiber_adapter_serial,

                    'large_viewfinder_model' => $request->large_viewfinder_model,
                    'large_viewfinder_type' => $request->large_viewfinder_type,
                    'large_viewfinder_serial' => $request->large_viewfinder_serial,

                    'small_viewfinder_model' => $request->small_viewfinder_model,
                    'small_viewfinder_type' => $request->small_viewfinder_type,
                    'small_viewfinder_serial' => $request->small_viewfinder_serial,

                    'ssl_license' => $request->boolean('ssl_license'),
                ]
            );

            return;
        }

        $item->cameraDetail()->delete();
    }

    public function syncMonitorDetails(Request $request, Item $item): void
    {
        if ($this->isMonitorUnit($request->units_id)) {
            $item->monitorDetail()->updateOrCreate(
                ['item_id' => $item->id],
                [
                    'manufacturer' => $request->manufacturer,
                    'model' => $request->model,
                    'serial_number' => $request->serial_number,
                    'screen_size' => $request->screen_size,

                    'has_speakers' => $request->boolean('has_speakers'),
                    'has_headphone' => $request->boolean('has_headphone'),

                    'converter_number' => $request->converter_number,
                    'converter_model' => $request->converter_model,
                    'converter_audio' => $request->boolean('converter_audio'),

                    'max_input_format' => $request->max_input_format,

                    'has_stand' => $request->boolean('has_stand'),
                    'stand_number' => $request->stand_number,
                ]
            );

            return;
        }

        $item->monitorDetail()->delete();
    }

    public function syncLensDetails(Request $request, Item $item): void
    {
        if ((int) $request->units_id === 2) {
            $item->lensDetail()->updateOrCreate(
                ['item_id' => $item->id],
                [
                    'manufacturer' => $request->lens_manufacturer,
                    'model' => $request->lens_model,
                    'serial_number' => $request->lens_serial_number,
                    'zoom_factor' => $request->lens_zoom_factor,
                    'zoom_servo_model' => $request->lens_zoom_servo_model,
                    'zoom_servo_serial_number' => $request->lens_zoom_servo_serial_number,
                    'focus_servo_model' => $request->lens_focus_servo_model,
                    'focus_servo_serial_number' => $request->lens_focus_servo_serial_number,
                ]
            );

            return;
        }

        $item->lensDetail()->delete();
    }

    private function isMonitorUnit(int|string|null $unitId): bool
    {
        if (! $unitId) {
            return false;
        }

        return Unit::where('id', $unitId)
            ->whereIn('bezeichnung', [
                'Monitore bis 24 Zoll',
                'Monitore über 24 Zoll',
            ])
            ->exists();
    }
}

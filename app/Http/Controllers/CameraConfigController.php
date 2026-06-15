<?php

namespace App\Http\Controllers;

use App\Models\CameraConfig;
use App\Models\Item;
use App\Models\Production;
use Illuminate\Http\Request;

class CameraConfigController extends Controller
{
    public function edit($id)
    {
        $config = CameraConfig::findOrFail($id);

        $cameras  = Item::where('units_id', 1)->orderBy('bezeichnung')->get();
        $lenses   = Item::where('units_id', 2)->orderBy('bezeichnung')->get();
        $tripods  = Item::where('units_id', 3)->orderBy('bezeichnung')->get();
        $heads    = Item::where('units_id', 4)->orderBy('bezeichnung')->get();
        $adapters = Item::where('units_id', 5)->orderBy('bezeichnung')->get();

        return view('camera_configs.edit', compact(
            'config',
            'cameras',
            'lenses',
            'tripods',
            'heads',
            'adapters'
        ));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'cam_number'         => 'required|string|max:255',
            'camera'             => 'required|exists:items,id',
            'lens'               => 'nullable|exists:items,id',
            'tripod'             => 'nullable|exists:items,id',
            'tripod_head'        => 'nullable|exists:items,id',
            'large_lens_adapter' => 'nullable|exists:items,id',
            'notes'              => 'nullable|string|max:2000',
        ]);

        $config = CameraConfig::findOrFail($id);
        $production = Production::findOrFail($config->production_id);

        $selectedItemIds = collect([
            $validated['camera'],
            $validated['lens'] ?? null,
            $validated['tripod'] ?? null,
            $validated['tripod_head'] ?? null,
            $validated['large_lens_adapter'] ?? null,
        ])
            ->filter()
            ->unique();

        foreach ($selectedItemIds as $itemId) {
            $item = Item::findOrFail($itemId);

            $availability = $this->checkItemAvailability($item, $production, $config);

            if (! $availability['available']) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with(
                        'error',
                        $item->bezeichnung . ': ' . $availability['reason']
                    );
            }
        }

        $config->cam_number         = $validated['cam_number'];
        $config->item_id            = $validated['camera'];
        $config->lens               = $validated['lens'] ?? null;
        $config->tripod             = $validated['tripod'] ?? null;
        $config->tripod_head        = $validated['tripod_head'] ?? null;
        $config->large_lens_adapter = $validated['large_lens_adapter'] ?? null;
        $config->notes              = $validated['notes'] ?? null;

        $config->save();

        return redirect()
            ->route('productions.show', $config->production_id)
            ->with('success', 'Kamera-Konfiguration aktualisiert.');
    }

    public function destroy($id)
    {
        $config = CameraConfig::findOrFail($id);
        $config->delete();

        return redirect()
            ->back()
            ->with('success', 'Kamera-Konfiguration erfolgreich entfernt.');
    }

    private function cameraConfigItemColumns(): array
    {
        return ['item_id', 'lens', 'tripod', 'tripod_head', 'large_lens_adapter'];
    }

    private function itemIsInCameraConfigQuery($query, int $itemId): void
    {
        $query->where(function ($q) use ($itemId) {
            foreach ($this->cameraConfigItemColumns() as $column) {
                $q->orWhere($column, $itemId);
            }
        });
    }

    private function checkItemAvailability(Item $item, Production $production, ?CameraConfig $ignoreConfig = null): array
    {
        if ($item->suppliers_id) {
            if ($item->rent_start && $item->rent_start > $production->booking_start) {
                return [
                    'available' => false,
                    'reason' => 'Mietbeginn zu spät',
                ];
            }

            if ($item->rent_end && $item->rent_end < $production->booking_end) {
                return [
                    'available' => false,
                    'reason' => 'Mietende zu früh',
                ];
            }
        }

        $directConflict = $item->productions()
            ->where('productions.booking_start', '<=', $production->booking_end)
            ->where('productions.booking_end', '>=', $production->booking_start)
            ->first();

        if ($directConflict) {
            return [
                'available' => false,
                'reason' => 'Direkt gebucht in Produktion: ' . $directConflict->bezeichnung,
            ];
        }

        $configConflictQuery = CameraConfig::query()
            ->whereHas('production', function ($q) use ($production) {
                $q->where('booking_start', '<=', $production->booking_end)
                  ->where('booking_end', '>=', $production->booking_start);
            });

        if ($ignoreConfig) {
            $configConflictQuery->where('id', '!=', $ignoreConfig->id);
        }

        $this->itemIsInCameraConfigQuery($configConflictQuery, $item->id);

        $configConflict = $configConflictQuery
            ->with('production')
            ->first();

        if ($configConflict) {
            return [
                'available' => false,
                'reason' => 'In Kamerakonfiguration: ' . $configConflict->production->bezeichnung,
            ];
        }

        return [
            'available' => true,
            'reason' => null,
        ];
    }
}
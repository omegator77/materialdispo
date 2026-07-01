<?php

namespace App\Http\Controllers;

use App\Http\Requests\CameraConfigRequest;
use App\Models\CameraConfig;
use App\Models\Item;
use App\Models\Production;
use App\Services\CameraConfigService;
use App\Services\ItemAvailabilityService;
use Illuminate\Http\Request;

class CameraConfigController extends Controller
{
    public function __construct(
        private CameraConfigService $cameraConfigs,
        private ItemAvailabilityService $availability,
    ) {}

    public function create(Production $production, Request $request)
    {
        $preselectedCameraId = (int) $request->query('camera_item_id');

        if ($preselectedCameraId) {
            $camera = Item::findOrFail($preselectedCameraId);

            $availability = $this->availability->check($camera, $production);

            if (! $availability['available']) {
                return redirect()
                    ->route('productions.show', $production)
                    ->with('error', $camera->bezeichnung.': '.$availability['reason']);
            }
        }

        $cameras = Item::where('units_id', 1)->orderBy('bezeichnung')->get();
        $lenses = Item::where('units_id', 2)->orderBy('bezeichnung')->get();
        $tripods = Item::where('units_id', 3)->orderBy('bezeichnung')->get();
        $heads = Item::where('units_id', 4)->orderBy('bezeichnung')->get();
        $adapters = Item::where('units_id', 5)->orderBy('bezeichnung')->get();

        return view('camera_configs.create', compact(
            'production',
            'cameras',
            'lenses',
            'tripods',
            'heads',
            'adapters',
            'preselectedCameraId'
        ));
    }

    public function store(CameraConfigRequest $request, Production $production)
    {
        $result = $this->cameraConfigs->create($production, $request->validated());

        if (! $result['success']) {
            return redirect()->back()->withInput()->with('error', $result['error']);
        }

        return redirect()
            ->route('productions.show', $production)
            ->with('success', 'Kamera-Konfiguration gespeichert.');
    }

    public function edit(CameraConfig $config)
    {
        $cameras = Item::where('units_id', 1)->orderBy('bezeichnung')->get();
        $lenses = Item::where('units_id', 2)->orderBy('bezeichnung')->get();
        $tripods = Item::where('units_id', 3)->orderBy('bezeichnung')->get();
        $heads = Item::where('units_id', 4)->orderBy('bezeichnung')->get();
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

    public function update(CameraConfigRequest $request, CameraConfig $config)
    {
        $result = $this->cameraConfigs->update($config, $request->validated());

        if (! $result['success']) {
            return redirect()->back()->withInput()->with('error', $result['error']);
        }

        return redirect()
            ->route('productions.show', $config->production_id)
            ->with('success', 'Kamera-Konfiguration aktualisiert.');
    }

    public function destroy(CameraConfig $config)
    {
        $config->delete();

        return redirect()
            ->back()
            ->with('success', 'Kamera-Konfiguration erfolgreich entfernt.');
    }
}

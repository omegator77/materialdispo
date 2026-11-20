<?php

namespace App\Http\Controllers;

use App\Http\Requests\CameraConfigRequest;
use App\Models\CameraConfig;
use App\Models\Geraetetyp;
use App\Models\Item;
use App\Models\Production;
use App\Services\CameraConfigService;
use App\Services\CameraSetupService;
use App\Services\ItemAvailabilityService;
use App\Services\SlackVorgangSync;
use Illuminate\Http\Request;

class CameraConfigController extends Controller
{
    public function __construct(
        private CameraConfigService $cameraConfigs,
        private ItemAvailabilityService $availability,
        private SlackVorgangSync $slack,
        private CameraSetupService $setup,
    ) {}

    public function create(Production $production, Request $request)
    {
        $preselectedCameraId = (int) $request->query('camera_item_id');

        if ($preselectedCameraId) {
            $camera = Item::findOrFail($preselectedCameraId);

            $availability = $this->availability->check($camera, $production);
            $reason = $this->availability->productionOverlap($camera, $production)
                ?? ($availability['available'] ? null : $availability['reason']);

            if ($reason) {
                return redirect()
                    ->route('productions.show', $production)
                    ->with('error', $camera->bezeichnung.': '.$reason);
            }
        }

        $cameras = Item::where('units_id', 1)->orderBy('bezeichnung')->get();
        $lenses = Item::where('units_id', 2)->orderBy('bezeichnung')->get();
        $tripods = Item::where('units_id', 3)->orderBy('bezeichnung')->get();
        $heads = Item::where('units_id', 4)->orderBy('bezeichnung')->get();
        $adapters = Item::where('units_id', 5)->orderBy('bezeichnung')->get();

        // Gelerntes Setup (aus früheren Zügen) als Vorbelegung — überschreibbar.
        $suggested = [];
        $suggestedBasis = 0;
        if ($preselectedCameraId) {
            $suggestion = $this->setup->suggestFor($production, $camera);
            $suggested = array_map(fn ($i) => $i?->id, $suggestion['items']);
            $suggestedBasis = $suggestion['basis'];
        }
        $suggestedCamNumber = 'Cam '.$this->setup->nextCamNumber($production);

        $blocked = $this->availability->unavailableReasons(
            $production,
            $cameras->concat($lenses)->concat($tripods)->concat($heads)->concat($adapters)
        );

        return view('camera-configs.create', compact(
            'blocked',
            'production',
            'cameras',
            'lenses',
            'tripods',
            'heads',
            'adapters',
            'preselectedCameraId',
            'suggested',
            'suggestedBasis',
            'suggestedCamNumber'
        ));
    }

    /** Mehrere Kamerazüge auf einmal, Setup aus der Historie gelernt. */
    public function auto(Request $request, Production $production)
    {
        $data = $request->validate([
            'geraetetyp_id' => ['required', 'exists:geraetetypen,id'],
            'anzahl' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $result = $this->setup->createRigs($production, Geraetetyp::findOrFail($data['geraetetyp_id']), (int) $data['anzahl']);
        $count = count($result['created']);

        $redirect = redirect()->route('productions.show', $production);

        if (! $count) {
            return $redirect->with('error', implode(' ', $result['warnings']) ?: 'Keine Kamerazüge angelegt.');
        }

        $hint = $result['basis'] ? " (Setup aus {$result['basis']} früheren Zügen übernommen)" : ' (nur Kameras — noch keine Historie für Zubehör)';
        $redirect->with('success', "{$count} Kamerazug/-züge angelegt{$hint}.");

        return $result['warnings'] ? $redirect->with('warning', implode(' ', $result['warnings'])) : $redirect;
    }

    public function store(CameraConfigRequest $request, Production $production)
    {
        $result = $this->cameraConfigs->create($production, $request->validated());

        if (! $result['success']) {
            return redirect()->back()->withInput()->with('error', $result['error']);
        }

        $this->slack->syncProduction($production);

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

        $blocked = $this->availability->unavailableReasons(
            $config->production,
            $cameras->concat($lenses)->concat($tripods)->concat($heads)->concat($adapters),
            $config
        );

        return view('camera-configs.edit', compact(
            'blocked',
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

        $this->slack->syncProduction($config->production);

        return redirect()
            ->route('productions.show', $config->production_id)
            ->with('success', 'Kamera-Konfiguration aktualisiert.');
    }

    public function destroy(CameraConfig $config)
    {
        $production = $config->production;

        $config->delete();

        $this->slack->syncProduction($production);

        return redirect()
            ->back()
            ->with('success', 'Kamera-Konfiguration erfolgreich entfernt.');
    }
}

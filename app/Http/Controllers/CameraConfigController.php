<?php

namespace App\Http\Controllers;

use App\Models\CameraConfig;
use App\Models\Item;
use App\Models\Production;
use App\Services\ItemAvailabilityService;
use Illuminate\Http\Request;



class CameraConfigController extends Controller
{
    /**
     * Formular zum Konfigurieren einer Kamera anzeigen.
     * Route (GET): camera-config.create
     * /productions/{production}/items/{item}/configure
     */
    public function create($productionId, $itemId)
    {
        $production = Production::findOrFail($productionId);
        $item       = Item::findOrFail($itemId);

        // Dropdown-Quellen (Units: 2=Objektive, 3=Stative, 4=Köpfe, 5=Adapter)
        $lenses  = Item::where('units_id', 2)->orderBy('bezeichnung')->get();
        $tripods = Item::where('units_id', 3)->orderBy('bezeichnung')->get();
        $heads   = Item::where('units_id', 4)->orderBy('bezeichnung')->get();
        $lladap  = Item::where('units_id', 5)->orderBy('bezeichnung')->get();

        // Einfache Vorschlagsnummer je Produktion (CAM-1, CAM-2, …)
        $suggestedCamNumber = 'CAM-'.(CameraConfig::where('production_id', $production->id)->count() + 1);

        return view('camera_configs.create', compact(
            'production','item','lenses','tripods','heads','lladap','suggestedCamNumber'
        ));
    }

    /**
     * Speichern der CameraConfig MIT Verfügbarkeitsprüfung & Buchung (1-Schritt-Flow).
     * Route (POST): camera-config.store
     * /productions/{production}/items/{item}/configure
     */
    public function store(
        Request $request,
        $productionId,
        $itemId, // Kamera-ID aus der URL
        ItemAvailabilityService $booking // zentraler Service für Prüfung & Buchung
    ) {
        $production = Production::findOrFail($productionId);
        $cameraId   = (int) $itemId;

        // IDs aus dem Form einsammeln (können leer sein)
        $ids = array_values(array_unique(array_filter([
            $cameraId,
            $this->toIntOrNull($request->input('lens')),
            $this->toIntOrNull($request->input('tripod')),
            $this->toIntOrNull($request->input('tripod_head')),
            $this->toIntOrNull($request->input('large_lens_adapter')),
        ])));

        try {
            // 1) Verfügbarkeit prüfen UND buchen (Pivot) – zentral & atomar
            $booking->bookAll($production, $ids);

            // 2) Wenn Buchung OK → CameraConfig anlegen
            CameraConfig::create([
                'production_id'      => $production->id,
                'item_id'            => $cameraId, // die Kamera
                'cam_number'         => $request->input('cam_number'),
                'cam_position'       => $request->input('cam_position'),
                // In diesen Feldern speichern wir die jeweilige Item-ID (oder null)
                'lens'               => $this->toIntOrNull($request->input('lens')),
                'tripod'             => $this->toIntOrNull($request->input('tripod')),
                'tripod_head'        => $this->toIntOrNull($request->input('tripod_head')),
                'large_lens_adapter' => $this->toIntOrNull($request->input('large_lens_adapter')),
                'notes'              => $request->input('notes'),
            ]);

            return redirect()
                ->route('productions.show', $production->id)
                ->with('success', 'Konfiguration gespeichert und gebucht (Kamera + Zubehör).');

        } catch (\RuntimeException $e) {
            // Buchung fehlgeschlagen → NICHT anlegen, Form mit Eingaben + Fehler zurück
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Optional: CameraConfig löschen (löscht NICHT automatisch Buchungen im Pivot).
     * Route (DELETE): camera-config.destroy
     * /camera-configs/{config}
     */
    public function destroy($configId)
    {
        $config = CameraConfig::findOrFail($configId);
        $productionId = $config->production_id;

        $config->delete();

        return redirect()
            ->route('productions.show', $productionId)
            ->with('success', 'Kamera-Konfiguration gelöscht.');
    }

    // ---- Helpers -------------------------------------------------------------

    private function toIntOrNull($v): ?int
    {
        return (is_numeric($v) && $v !== '') ? (int)$v : null;
    }
}

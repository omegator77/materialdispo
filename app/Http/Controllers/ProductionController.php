<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\Item;
use App\Models\Unit;
use App\Models\CameraConfig;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Validation\Rule;
use App\Services\ItemAvailabilityService;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $selectedProduction = $request->get('production_id', null);

        if ($selectedProduction) {
            $productions = Production::where('id', $selectedProduction)->get();
        } else {
            $productions = Production::all();
        }

        return view('productions.index', [
            'productions' => $productions,
            'allProductions' => Production::all(), // Um alle Produktionen anzuzeigen
            'selectedProduction' => $selectedProduction,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $productions = Production::all();

        return view('productions.create', compact('productions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'bezeichnung' => 'required',
            'booking_start' => 'date_format:d.m.Y|required',
            'booking_end' => 'date_format:d.m.Y|after_or_equal:booking_start|required',

        ]);

        // Die Datumsfelder für die Speicherung in der Datenbank formatieren
        $bookingStart = $request->booking_start ? \Carbon\Carbon::createFromFormat('d.m.Y', $request->booking_start)->format('Y-m-d') : null;
        $bookingEnd = $request->booking_end ? \Carbon\Carbon::createFromFormat('d.m.Y', $request->booking_end)->format('Y-m-d') : null;

        Production::create([
            'bezeichnung' => $request->input('bezeichnung'),
            'booking_start' => $bookingStart,
            'booking_end' => $bookingEnd,

        ]);

        return redirect('/productions');
    }

    /**
     * Display the specified resource.
     */
    public function show($id, Request $request)
    {
        $production = Production::with([
            'items.unit',
            'cameraConfigs.item.unit',
            'cameraConfigs.lensItem',
            'cameraConfigs.tripodItem',
            'cameraConfigs.headItem',
            'cameraConfigs.adapItem',
        ])->findOrFail($id);

        $unitFilter = $request->get('unit', null);

        $availableItems = Item::whereDoesntHave('productions', function ($query) use ($id) {
            $query->where('production_id', $id);
        })->whereDoesntHave('cameraConfigs', function ($query) use ($id) {
            $query->where('production_id', $id);
        });

        if ($unitFilter) {
            $availableItems->where('units_id', $unitFilter);
        }

        $availableItems = $availableItems->get();
        $allUnits = Unit::all();

        return view('productions.show', compact('production', 'availableItems', 'unitFilter', 'allUnits'));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Production $production)
    {
        $productions = Production::all();
        return view('productions.edit', compact('production', 'productions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Production $production)
    {
        try {
            // Validierung der Eingaben
            $request->validate([
                'bezeichnung' => 'required',
                'booking_start' => 'date_format:d.m.Y|required',
                'booking_end' => 'date_format:d.m.Y|after_or_equal:booking_start|required',
            ]);

            // Die Datumsfelder für die Speicherung in der Datenbank formatieren
            $bookingStart = $request->booking_start
                ? \Carbon\Carbon::createFromFormat('d.m.Y', $request->booking_start)->format('Y-m-d')
                : null;

            $bookingEnd = $request->booking_end
                ? \Carbon\Carbon::createFromFormat('d.m.Y', $request->booking_end)->format('Y-m-d')
                : null;

            // Aktualisierung der Produktion
            $production->update([
                'bezeichnung' => $request->input('bezeichnung'),
                'booking_start' => $bookingStart,
                'booking_end' => $bookingEnd,
            ]);

            // Weiterleitung mit Erfolgsnachricht
            return redirect('/productions')->with('success', 'Produktion erfolgreich aktualisiert.');
        } catch (Exeption $e) {
            return redirect()->back()->with('error', 'Fehler beim Aktualisieren: ' . $e->getMessage());
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Production::where('id', $id)->delete();
        return redirect('/productions');
    }

    public function attachItem(Request $request, $id, ItemAvailabilityService $booking)
{
    // Basic-Validierung: existierende Item-ID
    $validated = $request->validate([
        'item_id' => 'required|integer|exists:items,id',
        // 'unit' => 'nullable'  // falls du den Filter im Redirect brauchst
    ]);

    $production = Production::findOrFail($id);

    try {
        // prüft Verfügbarkeit (Mietzeit/Overlap) und bucht in einem Rutsch
        $booking->bookAll($production, [(int)$validated['item_id']]);

        return redirect()
            ->route('productions.show', [
                'production' => $production->id,
                'unit'       => $request->input('unit') // erhält deinen UI-Filter
            ])
            ->with('success', 'Item erfolgreich zugewiesen.');
    } catch (\RuntimeException $e) {
        // saubere Fehlermeldung zurück in die UI
        return redirect()
            ->route('productions.show', [
                'production' => $production->id,
                'unit'       => $request->input('unit')
            ])
            ->with('error', $e->getMessage());
    }
}


/*    public function attachItem(Request $request, $id)
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
        ]);

        try {
            $production = Production::findOrFail($id);

            // Retrieve booking dates
            $newStart = $production->booking_start;
            $newEnd = $production->booking_end;

            // Retrieve item details
            $item = Item::findOrFail($request->item_id);

            if ($item->is_rented) {
                // Validate rental period overlap
                $rentStart = $item->rent_start;
                $rentEnd = $item->rent_end;


                if (!($rentStart <= $newStart && $rentEnd >= $newEnd)) {
                    // Production period falls outside the rental period
                    return redirect()->route('productions.show', [
                        'production' => $id,
                        'unit' => $request->unit,
                    ])->with('error', 'Das gemietete Item kann nicht zugewiesen werden, da der Produktionszeitraum außerhalb des Mietzeitraums liegt.');
                }
            } else {
                // Check for booking conflicts for owned items
                $conflict = DB::table('item_production')
                    ->join('productions', 'item_production.production_id', '=', 'productions.id')
                    ->where('item_production.item_id', $request->item_id)
                    ->where(function ($query) use ($newStart, $newEnd) {
                        $query->where('productions.booking_start', '<=', $newEnd)
                            ->where('productions.booking_end', '>=', $newStart);
                    })
                    ->exists();

                if ($conflict) {
                    return redirect()->route('productions.show', [
                        'production' => $id,
                        'unit' => $request->unit,
                    ])->with('error', 'Das Item ist im angegebenen Zeitraum bereits gebucht.');
                }
            }

            // Perform the attachment in a transaction
            DB::transaction(function () use ($production, $request) {
                $production->items()->attach($request->item_id);
            });

            return redirect()->route('productions.show', [
                'production' => $id,
                'unit' => $request->unit,
            ])->with('success', 'Item erfolgreich zugewiesen.');
        } catch (ModelNotFoundException $e) {
            return redirect()->route('productions.index')->with('error', 'Production oder Item nicht gefunden.');
        } catch (\Exception $e) {
            return redirect()->route('productions.index')->with('error', 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
        }
    }
*/



    public function detachItem($id, $itemId)
    {
        $production = Production::findOrFail($id);
        $production->items()->detach($itemId);

        return Redirect::route('productions.show', ['production' => $id])->with('success', 'Item erfolgreich entfernt.');
    }

    public function requirements($id)
    {
        // Lade die Produktion anhand der ID
        $production = Production::findOrFail($id);

        // Übergebe die Produktion an die View
        return view('productions.requirements', compact('production'));
    }

    public function generatePDF($id)
    {
        // Produktion abrufen
        $production = Production::with('items')->findOrFail($id);

        // Kamera-Konfigurationen abrufen
        $cameraConfigs = CameraConfig::with('item.unit')
            ->where('production_id', $id)
            ->get();

        // Daten an die View übergeben
        $data = [
            'production' => $production,
            'items' => $production->items, // Alle gebuchten Items
            'cameraConfigs' => $cameraConfigs, // Kamera-Konfigurationen
        ];

        // PDF generieren
        $pdf = PDF::loadView('pdf.production_items', $data);

        return $pdf->download("{$production->bezeichnung}.pdf");
    }

/*
    public function showCameraConfigForm($productionId, $itemId)
    {
        $production = \App\Models\Production::findOrFail($productionId);
        $item       = \App\Models\Item::findOrFail($itemId);

        // Objektive (Unit „Objektive“ → Fallback 2)
        $lensUnitId = \App\Models\Unit::where('bezeichnung', 'Objektive')->value('id') ?? 2;
        $lenses = \App\Models\Item::where('units_id', $lensUnitId)
            ->orderBy('bezeichnung')
            ->get();

        // Stative (Unit „Stative“ → Fallback 3)
        $tripodUnitId = \App\Models\Unit::where('bezeichnung', 'Stative')->value('id') ?? 3;
        $tripods = \App\Models\Item::where('units_id', $tripodUnitId)
            ->orderBy('bezeichnung')
            ->get();

        // Stativköpfe (4)
        $heads   = \App\Models\Item::where('units_id', 4)->orderBy('bezeichnung')->get();

        //Large-Lens-Adapter (Unit 5) -> Abkürzung "lladap"
        $lladap  = \App\Models\Item::where('units_id', 5)->orderBy('bezeichnung')->get();


        return view('camera_configs.create', compact('production', 'item', 'lenses', 'tripods', 'heads', 'lladap'));
    }


*/

/*
public function storeCameraConfig(Request $request, $productionId, $itemId)
{
    // (optional) harte Unit-Checks; kannst du auch rauslassen, wenn egal:
    $UNIT_LENS = 2; $UNIT_TRIPOD = 3; $UNIT_HEAD = 4; $UNIT_ADAP = 5;

    $validated = $request->validate([
        'cam_number'         => 'required|string|max:255',
        'cam_position'       => 'nullable|string|max:255',
        'lens'               => ['nullable', Rule::exists('items','id')->where(fn($q)=>$q->where('units_id',$UNIT_LENS))],
        'tripod'             => ['nullable', Rule::exists('items','id')->where(fn($q)=>$q->where('units_id',$UNIT_TRIPOD))],
        'tripod_head'        => ['nullable', Rule::exists('items','id')->where(fn($q)=>$q->where('units_id',$UNIT_HEAD))],
        'large_lens_adapter' => ['nullable', Rule::exists('items','id')->where(fn($q)=>$q->where('units_id',$UNIT_ADAP))],
        'notes'              => 'nullable|string',
    ]);

    CameraConfig::create([
        'production_id'      => $productionId,
        'item_id'            => $itemId, // die KAMERA (Item-ID aus der URL)
        'cam_number'         => $validated['cam_number'],
        'cam_position'       => $validated['cam_position'] ?? null,

        // WICHTIG: hier speichern wir jetzt wirklich **Item-IDs**
        'lens'               => $validated['lens'] ?? null,
        'tripod'             => $validated['tripod'] ?? null,
        'tripod_head'        => $validated['tripod_head'] ?? null,
        'large_lens_adapter' => $validated['large_lens_adapter'] ?? null,

        'notes'              => $validated['notes'] ?? null,
    ]);

    return redirect()->route('productions.show', $productionId)
        ->with('success', 'Kamera-Konfiguration gespeichert.');
}
*/
    }

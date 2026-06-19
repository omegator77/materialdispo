<?php

namespace App\Http\Controllers;

use App\Models\CameraConfig;
use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $selectedProduction = $request->get('production_id');

        if ($selectedProduction) {
            $productions = Production::where('id', $selectedProduction)->get();
        } else {
            $productions = Production::all();
        }

        return view('productions.index', [
            'productions' => $productions,
            'allProductions' => Production::all(),
            'selectedProduction' => $selectedProduction,
        ]);
    }

    public function create()
    {
        $productions = Production::all();

        return view('productions.create', compact('productions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'bezeichnung' => 'required',
            'booking_start' => 'date_format:d.m.Y|required',
            'booking_end' => 'date_format:d.m.Y|after_or_equal:booking_start|required',
            'packlist_notes' => 'nullable|string',
        ]);

        $bookingStart = \Carbon\Carbon::createFromFormat('d.m.Y', $request->booking_start)->format('Y-m-d');
        $bookingEnd = \Carbon\Carbon::createFromFormat('d.m.Y', $request->booking_end)->format('Y-m-d');

        Production::create([
            'bezeichnung' => $request->input('bezeichnung'),
            'booking_start' => $bookingStart,
            'booking_end' => $bookingEnd,
            'packlist_notes' => $request->packlist_notes,
        ]);

        return redirect('/productions');
    }

    public function show($id, Request $request)
    {
        $production = Production::with([
            'items.unit',
            'cameraConfigs.item.unit',
            'cameraConfigs.lensItem',
            'cameraConfigs.tripodItem',
            'cameraConfigs.headItem',
            'cameraConfigs.adapterItem',
        ])->findOrFail($id);

        $unitFilter = $request->get('unit');
        $showUnavailable = $request->boolean('show_unavailable');

        $itemsQuery = Item::query()
            ->whereDoesntHave('productions', function ($query) use ($id) {
                $query->where('productions.id', $id);
            })
            ->where(function ($query) use ($id) {
                $query
                    ->whereDoesntHave('cameraConfigs', function ($q) use ($id) {
                        $q->where('production_id', $id);
                    })
                    ->whereNotIn('id', function ($q) use ($id) {
                        $q->select('lens')
                            ->from('camera_configs')
                            ->where('production_id', $id)
                            ->whereNotNull('lens');
                    })
                    ->whereNotIn('id', function ($q) use ($id) {
                        $q->select('tripod')
                            ->from('camera_configs')
                            ->where('production_id', $id)
                            ->whereNotNull('tripod');
                    })
                    ->whereNotIn('id', function ($q) use ($id) {
                        $q->select('tripod_head')
                            ->from('camera_configs')
                            ->where('production_id', $id)
                            ->whereNotNull('tripod_head');
                    })
                    ->whereNotIn('id', function ($q) use ($id) {
                        $q->select('large_lens_adapter')
                            ->from('camera_configs')
                            ->where('production_id', $id)
                            ->whereNotNull('large_lens_adapter');
                    });
            });

        if ($unitFilter) {
            $itemsQuery->where('units_id', $unitFilter);
        }

        $availableItems = $itemsQuery
            ->orderBy('bezeichnung')
            ->get()
            ->map(function ($item) use ($production) {
                $availability = $this->checkItemAvailability($item, $production);

                $item->is_available = $availability['available'];
                $item->availability_reason = $availability['reason'];

                return $item;
            });

        if (! $showUnavailable) {
            $availableItems = $availableItems
                ->filter(fn ($item) => $item->is_available)
                ->values();
        }

        $allUnits = Unit::orderBy('bezeichnung')->get();

        return view('productions.show', compact(
            'production',
            'availableItems',
            'unitFilter',
            'allUnits'
        ));
    }

    public function edit(Production $production)
    {
        $productions = Production::all();

        return view('productions.edit', compact('production', 'productions'));
    }

    public function update(Request $request, Production $production)
    {
        $request->validate([
            'bezeichnung' => 'required',
            'booking_start' => 'date_format:d.m.Y|required',
            'booking_end' => 'date_format:d.m.Y|after_or_equal:booking_start|required',
            'packlist_notes' => 'nullable|string',
        ]);

        $bookingStart = \Carbon\Carbon::createFromFormat('d.m.Y', $request->booking_start)->format('Y-m-d');
        $bookingEnd = \Carbon\Carbon::createFromFormat('d.m.Y', $request->booking_end)->format('Y-m-d');

        $production->update([
            'bezeichnung' => $request->input('bezeichnung'),
            'booking_start' => $bookingStart,
            'booking_end' => $bookingEnd,
            'packlist_notes' => $request->packlist_notes,
        ]);

        return redirect('/productions')->with('success', 'Produktion erfolgreich aktualisiert.');
    }

    public function destroy(string $id)
    {
        Production::where('id', $id)->delete();

        return redirect('/productions');
    }

    public function attachItem(Request $request, $id)
    {
        $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $production = Production::findOrFail($id);
            $item = Item::findOrFail($request->item_id);

            $redirectParams = [
                'production' => $production->id,
            ];

            if ($request->filled('unit')) {
                $redirectParams['unit'] = $request->unit;
            }

            if ($request->boolean('show_unavailable')) {
                $redirectParams['show_unavailable'] = 1;
            }

            $availability = $this->checkItemAvailability($item, $production);

            if (! $availability['available']) {
                return redirect()
                    ->route('productions.show', $redirectParams)
                    ->with('error', $availability['reason']);
            }

            DB::transaction(function () use ($production, $item, $request) {
                $production->items()->syncWithoutDetaching([
                    $item->id => [
                        'notes' => $request->notes,
                    ],
                ]);
            });

            return redirect()
                ->route('productions.show', $redirectParams)
                ->with('success', 'Item erfolgreich zugewiesen.');
        } catch (ModelNotFoundException $e) {
            return redirect()
                ->route('productions.index')
                ->with('error', 'Produktion oder Item nicht gefunden.');
        } catch (\Exception $e) {
            return redirect()
                ->route('productions.index')
                ->with('error', 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
        }
    }

    public function detachItem($id, $itemId)
    {
        $production = Production::findOrFail($id);
        $production->items()->detach($itemId);

        return Redirect::route('productions.show', ['production' => $id])
            ->with('success', 'Item erfolgreich entfernt.');
    }

    public function requirements($id)
    {
        $production = Production::findOrFail($id);

        return view('productions.requirements', compact('production'));
    }

    public function generatePDF($id)
    {
        $production = Production::with([
            'items.unit',
            'cameraConfigs.item.unit',
            'cameraConfigs.lensItem',
            'cameraConfigs.tripodItem',
            'cameraConfigs.headItem',
            'cameraConfigs.adapterItem',
        ])->findOrFail($id);

        $data = [
            'production' => $production,
            'items' => $production->items,
            'cameraConfigs' => $production->cameraConfigs,
        ];

        $pdf = Pdf::loadView('pdf.production_items', $data);

        return $pdf->download("{$production->bezeichnung}.pdf");
    }

    public function storeCameraConfig(Request $request, Production $production)
    {
        $validated = $request->validate([
            'cam_number' => 'required|string|max:255',
            'camera' => 'required|exists:items,id',
            'lens' => 'nullable|exists:items,id',
            'tripod' => 'nullable|exists:items,id',
            'tripod_head' => 'nullable|exists:items,id',
            'large_lens_adapter' => 'nullable|exists:items,id',
            'notes' => 'nullable|string|max:2000',
        ]);

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

            $availability = $this->checkItemAvailability($item, $production);

            if (! $availability['available']) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', $item->bezeichnung . ': ' . $availability['reason']);
            }
        }

        $config = new CameraConfig();
        $config->production_id = $production->id;
        $config->item_id = $validated['camera'];
        $config->lens = $validated['lens'] ?? null;
        $config->tripod = $validated['tripod'] ?? null;
        $config->tripod_head = $validated['tripod_head'] ?? null;
        $config->large_lens_adapter = $validated['large_lens_adapter'] ?? null;
        $config->cam_number = $validated['cam_number'];
        $config->notes = $validated['notes'] ?? null;
        $config->save();

        return redirect()
            ->route('productions.show', $production)
            ->with('success', 'Kamera-Konfiguration gespeichert.');
    }

    public function createCameraConfig(Production $production, Request $request)
    {
        $preselectedCameraId = (int) $request->query('camera_item_id');

        if ($preselectedCameraId) {
            $camera = Item::findOrFail($preselectedCameraId);

            $availability = $this->checkItemAvailability($camera, $production);

            if (! $availability['available']) {
                return redirect()
                    ->route('productions.show', $production)
                    ->with('error', $camera->bezeichnung . ': ' . $availability['reason']);
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

    private function cameraConfigItemColumns(): array
    {
        return ['item_id', 'lens', 'tripod', 'tripod_head', 'large_lens_adapter'];
    }

    private function itemIsInCameraConfigQuery($query, int $itemId)
    {
        $query->where(function ($q) use ($itemId) {
            foreach ($this->cameraConfigItemColumns() as $column) {
                $q->orWhere($column, $itemId);
            }
        });
    }

    private function checkItemAvailability(Item $item, Production $production): array
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

        $conflict = $item->productions()
            ->where('productions.id', '!=', $production->id)
            ->where('booking_start', '<=', $production->booking_end)
            ->where('booking_end', '>=', $production->booking_start)
            ->first();

        if ($conflict) {
            return [
                'available' => false,
                'reason' => 'Gebucht in Produktion: ' . $conflict->bezeichnung,
            ];
        }

        $configConflict = CameraConfig::query()
            ->where('production_id', '!=', $production->id)
            ->whereHas('production', function ($q) use ($production) {
                $q->where('booking_start', '<=', $production->booking_end)
                    ->where('booking_end', '>=', $production->booking_start);
            });

        $this->itemIsInCameraConfigQuery($configConflict, $item->id);

        $configConflict = $configConflict
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
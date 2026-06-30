<?php

namespace App\Http\Controllers;

use App\Models\CameraConfig;
use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use App\Services\ItemAvailabilityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class ProductionController extends Controller
{
    public function __construct(private ItemAvailabilityService $availability) {}

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

    public function create(Request $request)
    {
        $productions = Production::orderBy('booking_start', 'desc')->limit(25)->get();
        $preset = $request->filled('from') ? Production::find($request->from) : null;

        return view('productions.create', compact('productions', 'preset'));
    }

    public function searchTemplates(Request $request)
    {
        $query = trim((string) $request->get('q', ''));

        $productions = Production::query()
            ->when($query !== '', fn ($q) => $q->where('bezeichnung', 'like', "%{$query}%"))
            ->orderBy('booking_start', 'desc')
            ->limit(20)
            ->get(['id', 'bezeichnung', 'booking_start']);

        return response()->json($productions->map(fn ($p) => [
            'id' => $p->id,
            'bezeichnung' => $p->bezeichnung,
            'booking_start' => $p->booking_start ? \Carbon\Carbon::parse($p->booking_start)->format('d.m.Y') : null,
        ]));
    }

    public function store(Request $request)
    {
        $request->validate([
            'bezeichnung'   => 'required',
            'booking_start' => 'date_format:d.m.Y|required',
            'booking_end'   => 'date_format:d.m.Y|after_or_equal:booking_start|required',
            'packlist_notes' => 'nullable|string',
        ]);

        $bookingStart = \Carbon\Carbon::createFromFormat('d.m.Y', $request->booking_start)->format('Y-m-d');
        $bookingEnd   = \Carbon\Carbon::createFromFormat('d.m.Y', $request->booking_end)->format('Y-m-d');

        $production = Production::create([
            'bezeichnung'    => $request->input('bezeichnung'),
            'booking_start'  => $bookingStart,
            'booking_end'    => $bookingEnd,
            'packlist_notes' => $request->packlist_notes,
        ]);

        if ($request->filled('from_production_id')) {
            $source = Production::find($request->from_production_id);
            if ($source) {
                return redirect()->route('productions.importFrom', [$production, $source]);
            }
        }

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
                $check = $this->availability->check($item, $production);

                $item->is_available = $check['available'];
                $item->availability_reason = $check['reason'];

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
            'item_id' => ['required'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $itemIds = collect((array) $request->input('item_id'))->filter()->unique()->values();

        try {
            $production = Production::findOrFail($id);

            $redirectParams = [
                'production' => $production->id,
            ];

            if ($request->filled('unit')) {
                $redirectParams['unit'] = $request->unit;
            }

            if ($request->boolean('show_unavailable')) {
                $redirectParams['show_unavailable'] = 1;
            }

            $items = Item::whereIn('id', $itemIds)->get()->keyBy('id');

            if ($items->count() !== $itemIds->count()) {
                return redirect()
                    ->route('productions.show', $redirectParams)
                    ->with('error', 'Ein oder mehrere Geräte wurden nicht gefunden.');
            }

            $added = [];
            $skipped = [];

            foreach ($itemIds as $itemId) {
                $item = $items->get($itemId);

                if ($production->items()->where('items.id', $item->id)->exists()) {
                    $skipped[] = "{$item->bezeichnung} (bereits enthalten)";
                    continue;
                }

                $availability = $this->availability->check($item, $production);

                if (! $availability['available']) {
                    $skipped[] = "{$item->bezeichnung} ({$availability['reason']})";
                    continue;
                }

                DB::transaction(function () use ($production, $item, $request) {
                    $production->items()->syncWithoutDetaching([
                        $item->id => [
                            'notes' => $request->notes,
                        ],
                    ]);
                });

                activity('item')
                    ->performedOn($item)
                    ->event('attached')
                    ->withProperties(['production' => $production->bezeichnung, 'production_id' => $production->id])
                    ->log("Gerät \"{$item->bezeichnung}\" zu Produktion \"{$production->bezeichnung}\" hinzugefügt");

                $added[] = $item->bezeichnung;
            }

            $messageParts = [];
            if (count($added)) {
                $messageParts[] = count($added) . ' Gerät(e) hinzugefügt: ' . implode(', ', $added);
            }
            if (count($skipped)) {
                $messageParts[] = 'Übersprungen: ' . implode(', ', $skipped);
            }

            $messageType = count($added) ? 'success' : 'error';
            $message = $messageParts ? implode(' — ', $messageParts) : 'Keine Geräte ausgewählt.';

            return redirect()
                ->route('productions.show', $redirectParams)
                ->with($messageType, $message);
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
        $item = Item::find($itemId);
        $production->items()->detach($itemId);

        if ($item) {
            activity('item')
                ->performedOn($item)
                ->event('detached')
                ->withProperties(['production' => $production->bezeichnung, 'production_id' => $production->id])
                ->log("Gerät \"{$item->bezeichnung}\" aus Produktion \"{$production->bezeichnung}\" entfernt");
        }

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

            $availability = $this->availability->check($item, $production);

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

    public function importFrom(Production $production, Production $source)
    {
        $source->load([
            'items.unit',
            'cameraConfigs.item.unit',
            'cameraConfigs.lensItem',
            'cameraConfigs.tripodItem',
            'cameraConfigs.headItem',
            'cameraConfigs.adapterItem',
        ]);

        // Einzelgeräte prüfen
        $itemResults = $source->items->map(function ($item) use ($production) {
            $check = $this->availability->check($item, $production);
            $alternatives = collect();
            if (! $check['available']) {
                $alternatives = Item::where('units_id', $item->units_id)
                    ->where('id', '!=', $item->id)
                    ->orderBy('bezeichnung')
                    ->get()
                    ->filter(fn ($alt) => $this->availability->check($alt, $production)['available'])
                    ->values();
            }
            return [
                'item'         => $item,
                'available'    => $check['available'],
                'reason'       => $check['reason'],
                'notes'        => $item->pivot->notes ?? null,
                'alternatives' => $alternatives,
            ];
        });

        // Kamerazüge prüfen
        $configResults = $source->cameraConfigs->map(function ($config) use ($production) {
            $slots = [
                'Kamera'      => $config->item,
                'Objektiv'    => $config->lensItem,
                'Stativ'      => $config->tripodItem,
                'Stativkopf'  => $config->headItem,
                'Adapter'     => $config->adapterItem,
            ];
            $conflicts = [];
            foreach ($slots as $label => $item) {
                if (! $item) continue;
                $check = $this->availability->check($item, $production);
                if (! $check['available']) {
                    $conflicts[$label] = ['item' => $item, 'reason' => $check['reason']];
                }
            }
            return [
                'config'    => $config,
                'slots'     => array_filter($slots),
                'available' => empty($conflicts),
                'conflicts' => $conflicts,
            ];
        });

        return view('productions.import-from', compact('production', 'source', 'itemResults', 'configResults'));
    }

    public function storeImport(Request $request, Production $production, Production $source)
    {
        // Einzelgeräte übernehmen
        foreach ($request->input('items', []) as $itemId => $data) {
            $action = $data['action'] ?? 'skip';

            if ($action === 'keep') {
                $production->items()->syncWithoutDetaching([
                    $itemId => ['notes' => $data['notes'] ?? null],
                ]);
            } elseif ($action === 'replace' && ! empty($data['replacement_id'])) {
                $replacement = Item::find($data['replacement_id']);
                if ($replacement && $this->availability->check($replacement, $production)['available']) {
                    $production->items()->syncWithoutDetaching([
                        $data['replacement_id'] => ['notes' => $data['notes'] ?? null],
                    ]);
                }
            }
        }

        // Kamerazüge übernehmen
        foreach ($request->input('configs', []) as $configId => $action) {
            if ($action !== 'import') continue;
            $config = $source->cameraConfigs->firstWhere('id', $configId);
            if (! $config) continue;

            $newConfig = new CameraConfig();
            $newConfig->production_id      = $production->id;
            $newConfig->item_id            = $config->item_id;
            $newConfig->lens               = $config->lens;
            $newConfig->tripod             = $config->tripod;
            $newConfig->tripod_head        = $config->tripod_head;
            $newConfig->large_lens_adapter = $config->large_lens_adapter;
            $newConfig->cam_number         = $config->cam_number;
            $newConfig->notes              = $config->notes;
            $newConfig->save();
        }

        return redirect()->route('productions.show', $production)
            ->with('success', 'Geräte aus Vorlage übernommen.');
    }

    public function createCameraConfig(Production $production, Request $request)
    {
        $preselectedCameraId = (int) $request->query('camera_item_id');

        if ($preselectedCameraId) {
            $camera = Item::findOrFail($preselectedCameraId);

            $availability = $this->availability->check($camera, $production);

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

}
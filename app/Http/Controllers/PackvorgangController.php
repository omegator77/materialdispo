<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Production;
use App\Models\ProductionItemPack;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PackvorgangController extends Controller
{
    public function show(Production $production)
    {
        $production->load(['items.unit', 'cameraConfigs.item.unit', 'cameraConfigs.lensItem', 'cameraConfigs.tripodItem', 'cameraConfigs.headItem', 'cameraConfigs.adapterItem', 'itemPacks', 'packvorgangConfirmedBy']);

        $entries = $production->packlistEntries();
        $packs = $production->itemPacks->keyBy('item_id');
        [$cameraGroups, $groupedEinzelEntries] = $this->groupEntries($entries);

        return view('packvorgang.show', compact('production', 'entries', 'cameraGroups', 'groupedEinzelEntries', 'packs'));
    }

    /**
     * Gruppiert die Soll-Geräteliste so, wie sie beim physischen Packen
     * gebraucht wird: Kamerazug-Geräte pro Konfiguration zusammen (werden
     * oft gemeinsam auf einen Rollwagen gepackt), restliche Geräte nach
     * Gerätegruppe (Einheit).
     */
    private function groupEntries(\Illuminate\Support\Collection $entries): array
    {
        $cameraGroups = $entries
            ->where('source', 'kamera')
            ->groupBy('config_id')
            ->sortBy(fn ($group) => $group->first()['cam_number']);

        $groupedEinzelEntries = $entries
            ->where('source', 'einzel')
            ->groupBy(fn ($entry) => $entry['item']->unit->bezeichnung ?? 'Ohne Gruppe');

        return [$cameraGroups, $groupedEinzelEntries];
    }

    public function toggle(Request $request, Production $production, Item $item)
    {
        if ($production->packvorgang_confirmed_at) {
            return response()->json([
                'error' => 'Packvorgang ist abgeschlossen und gesperrt.',
            ], 423);
        }

        $existing = ProductionItemPack::where('production_id', $production->id)
            ->where('item_id', $item->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $packed = false;

            activity('item')
                ->performedOn($item)
                ->event('unpacked')
                ->withProperties(['production' => $production->bezeichnung, 'production_id' => $production->id])
                ->log("Gerät \"{$item->bezeichnung}\" für Produktion \"{$production->bezeichnung}\" als nicht gepackt markiert");
        } else {
            ProductionItemPack::create([
                'production_id' => $production->id,
                'item_id' => $item->id,
                'packed_by' => auth()->id(),
                'packed_at' => now(),
            ]);
            $packed = true;

            activity('item')
                ->performedOn($item)
                ->event('packed')
                ->withProperties(['production' => $production->bezeichnung, 'production_id' => $production->id])
                ->log("Gerät \"{$item->bezeichnung}\" für Produktion \"{$production->bezeichnung}\" als gepackt markiert");
        }

        $production->load('itemPacks');

        return response()->json([
            'packed' => $packed,
            'packedBy' => $packed ? auth()->user()->name : null,
            'packedAt' => $packed ? now()->format('d.m.Y H:i') : null,
            'totalPacked' => $production->packedItemIds()->count(),
            'totalEntries' => $production->packlistEntries()->count(),
        ]);
    }

    public function complete(Request $request, Production $production)
    {
        $production->update([
            'packvorgang_confirmed_at' => now(),
            'packvorgang_confirmed_by' => auth()->id(),
        ]);

        return redirect()
            ->route('packvorgang.show', $production)
            ->with('success', 'Packvorgang als abgeschlossen markiert.');
    }

    public function reopen(Production $production)
    {
        $production->update([
            'packvorgang_confirmed_at' => null,
            'packvorgang_confirmed_by' => null,
        ]);

        return redirect()
            ->route('packvorgang.show', $production)
            ->with('success', 'Packvorgang wieder geöffnet.');
    }

    public function pdf(Production $production)
    {
        $production->load(['items.unit', 'cameraConfigs.item.unit', 'cameraConfigs.lensItem', 'cameraConfigs.tripodItem', 'cameraConfigs.headItem', 'cameraConfigs.adapterItem', 'itemPacks']);

        $entries = $production->packlistEntries();
        $packedIds = $production->packedItemIds();
        [$cameraGroups, $groupedEinzelEntries] = $this->groupEntries($entries);

        $pdf = Pdf::loadView('pdf.packvorgang_checklist', compact('production', 'cameraGroups', 'groupedEinzelEntries', 'packedIds'));

        return $pdf->download("Packvorgang {$production->bezeichnung}.pdf");
    }
}

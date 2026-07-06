<?php

namespace App\Http\Controllers;

use App\Models\DryHire;
use App\Models\Production;
use Illuminate\Http\Request;

class DryHireController extends Controller
{
    public function update(Request $request, Production $production)
    {
        $request->validate([
            'delivery_type' => ['nullable', 'in:'.implode(',', array_keys(DryHire::DELIVERY_TYPES))],
            'return_type' => ['nullable', 'in:'.implode(',', array_keys(DryHire::RETURN_TYPES))],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'notify_customer' => ['nullable', 'boolean'],
            'reminder_days_before_start' => ['nullable', 'integer', 'min:0', 'max:60'],
            'reminder_days_before_end' => ['nullable', 'integer', 'min:0', 'max:60'],
            'mailing_list_id' => ['nullable', 'exists:mailing_lists,id'],
        ]);

        DryHire::updateOrCreate(
            ['production_id' => $production->id],
            [
                'delivery_type' => $request->delivery_type ?: null,
                'return_type' => $request->return_type ?: null,
                'customer_email' => $request->customer_email ?: null,
                'notify_customer' => $request->boolean('notify_customer'),
                'reminder_days_before_start' => $request->reminder_days_before_start ?: null,
                'reminder_days_before_end' => $request->reminder_days_before_end ?: null,
                'mailing_list_id' => $request->mailing_list_id ?: null,
            ]
        );

        return redirect()->route('productions.show', $production)->with('success', 'Dry Hire aktualisiert.');
    }

    public function confirmTransport(Production $production, string $type)
    {
        abort_unless(in_array($type, ['start', 'end']), 404);

        $dryHire = $production->dryHire()->firstOrCreate([]);

        $dryHire->update([
            "{$type}_confirmed_at" => now(),
            "{$type}_confirmed_by" => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Transport als geklärt markiert.');
    }

    public function reopenTransport(Production $production, string $type)
    {
        abort_unless(in_array($type, ['start', 'end']), 404);

        $production->dryHire?->update([
            "{$type}_confirmed_at" => null,
            "{$type}_confirmed_by" => null,
        ]);

        return redirect()->back()->with('success', 'Wieder geöffnet.');
    }
}

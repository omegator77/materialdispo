<?php

namespace App\Support;

use App\Models\Mietvorgang;
use App\Models\Production;
use App\Models\Unit;
use App\Models\Vermietvorgang;
use Illuminate\Support\Collection;

/**
 * Zielauswahl der Massenaktions-Leiste (Geräteliste und Timeline).
 */
class BulkTargets
{
    /**
     * @return array{productions: Collection, mietvorgaenge: Collection, vermietvorgaenge: Collection, units: Collection}
     */
    public static function load(): array
    {
        $today = today()->toDateString();

        return [
            'productions' => Production::where('booking_end', '>=', $today)->orderBy('booking_start')->get(),
            'mietvorgaenge' => Mietvorgang::with('supplier')->where('rent_end', '>=', $today)->orderBy('rent_start')->get(),
            'vermietvorgaenge' => Vermietvorgang::with('mieter')->where('rent_end', '>=', $today)->orderBy('rent_start')->get(),
            'units' => Unit::ordered()->get(),
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Geraetetyp;
use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Mehrere Geräte in einem Schritt anlegen: je Zeile Typ + Anzahl +
 * Nummernbereich, optional gleich mit Vermieter/Mietzeitraum. Alle Geräte
 * einer Zeile landen im selben Mietvorgang (findOrCreateFor).
 */
class BulkItemService
{
    public const MAX_PER_ROW = 100;

    /**
     * "1-5, 8, 21-23" → ['1','2','3','4','5','8','21','22','23'].
     * Nicht-numerische Teile ("A12") bleiben unverändert; führende Nullen
     * eines Bereichs bleiben erhalten ("001-003").
     *
     * @return string[]
     */
    public function parseNumberSpec(?string $spec): array
    {
        $out = [];
        foreach (preg_split('/[\s,;]+/', trim((string) $spec)) ?: [] as $part) {
            if ($part === '') {
                continue;
            }
            if (preg_match('/^(\d+)\s*[-–]\s*(\d+)$/u', $part, $m) && (int) $m[2] >= (int) $m[1] && (int) $m[2] - (int) $m[1] < self::MAX_PER_ROW) {
                $width = strlen($m[1]) > 1 && $m[1][0] === '0' ? strlen($m[1]) : 0;
                for ($n = (int) $m[1]; $n <= (int) $m[2]; $n++) {
                    $out[] = $width ? str_pad((string) $n, $width, '0', STR_PAD_LEFT) : (string) $n;
                }
            } else {
                $out[] = $part;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Nächste freie Nummer in der Gruppe: höchste rein numerische Nummer + 1.
     */
    public function suggestNextNumber(int $unitId): ?string
    {
        $max = Item::where('units_id', $unitId)->pluck('nummer')
            ->filter(fn ($n) => $n !== null && ctype_digit(trim($n)))
            ->map(fn ($n) => (int) $n)
            ->max();

        return $max === null ? null : (string) ($max + 1);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{created: Item[], warnings: string[]}
     */
    public function createMany(array $rows): array
    {
        $created = [];
        $warnings = [];

        DB::transaction(function () use ($rows, &$created, &$warnings) {
            foreach ($rows as $index => $row) {
                $typ = ! empty($row['geraetetyp_id']) ? Geraetetyp::find($row['geraetetyp_id']) : null;
                $unitId = (int) ($row['units_id'] ?? $typ?->units_id);
                $bezeichnung = trim((string) ($row['bezeichnung'] ?? '')) ?: $typ?->bezeichnung;

                if (! $unitId || ! $bezeichnung) {
                    $warnings[] = 'Zeile '.($index + 1).': Gruppe/Bezeichnung fehlt — übersprungen.';

                    continue;
                }

                $numbers = $this->numbersFor($row, $unitId);
                $rowItems = [];

                foreach ($numbers as $nummer) {
                    if ($nummer !== null && Item::where('units_id', $unitId)->where('nummer', $nummer)->where('bezeichnung', $bezeichnung)->exists()) {
                        $warnings[] = "„{$bezeichnung}“ Nr. {$nummer} existiert bereits — übersprungen.";

                        continue;
                    }

                    $rowItems[] = Item::create([
                        'units_id' => $unitId,
                        'geraetetyp_id' => $typ?->id,
                        'suppliers_id' => $row['suppliers_id'] ?? null,
                        'bezeichnung' => $bezeichnung,
                        'nummer' => $nummer,
                        'description' => $row['description'] ?? null,
                    ]);
                }

                if (! empty($row['suppliers_id']) && ! empty($row['rent_start']) && ! empty($row['rent_end'])) {
                    $start = Carbon::createFromFormat('d.m.Y', $row['rent_start'])->format('Y-m-d');
                    $end = Carbon::createFromFormat('d.m.Y', $row['rent_end'])->format('Y-m-d');
                    foreach ($rowItems as $item) {
                        $result = $item->syncMietvorgang($start, $end);
                        if (! $result['added'] && ! $result['alreadyAttached']) {
                            $warnings[] = "„{$item->bezeichnung}“ {$item->nummer}: Mietzeitraum nicht zugeordnet ({$result['reason']}).";
                        }
                    }
                }

                array_push($created, ...$rowItems);
            }
        });

        return ['created' => $created, 'warnings' => $warnings];
    }

    /**
     * @return array<int, ?string>
     */
    private function numbersFor(array $row, int $unitId): array
    {
        $spec = $this->parseNumberSpec($row['nummern'] ?? null);
        if ($spec) {
            return array_slice($spec, 0, self::MAX_PER_ROW);
        }

        $anzahl = max(1, min(self::MAX_PER_ROW, (int) ($row['anzahl'] ?? 1)));
        $start = $row['nummer_start'] ?? $this->suggestNextNumber($unitId);

        if ($start === null || $start === '' || ! ctype_digit((string) $start)) {
            return array_fill(0, $anzahl, $start === '' ? null : $start);
        }

        $numbers = [];
        for ($i = 0; $i < $anzahl; $i++) {
            $numbers[] = (string) ((int) $start + $i);
        }

        return $numbers;
    }
}

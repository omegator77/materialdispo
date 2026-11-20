<?php

namespace App\Services;

use App\Models\Geraetetyp;
use App\Models\Unit;
use Illuminate\Support\Collection;

/**
 * Ordnet Freitext ("LDX", "Stative", "22fach") Gerätetypen bzw. Gruppen zu.
 * Rein regelbasiert (Normalisierung, Token-Präfixe, Tippfehler-Toleranz) —
 * Grundlage für Schnellerfassung, Schnell-Hinzufügen und die Suche.
 */
class EquipmentMatcher
{
    public const THRESHOLD = 0.6;

    public function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = strtr($text, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? '';

        return trim($text);
    }

    /** @return string[] */
    private function tokens(string $normalized): array
    {
        return $normalized === '' ? [] : explode(' ', $normalized);
    }

    public function score(string $query, string $candidate): float
    {
        $q = $this->normalize($query);
        $c = $this->normalize($candidate);

        if ($q === '' || $c === '') {
            return 0.0;
        }
        if ($q === $c) {
            return 1.0;
        }

        $qt = $this->tokens($q);
        $ct = $this->tokens($c);

        $hits = 0;
        foreach ($qt as $t) {
            foreach ($ct as $u) {
                if ($t === $u || (min(strlen($t), strlen($u)) >= 3 && (str_starts_with($u, $t) || str_starts_with($t, $u)))) {
                    $hits++;
                    break;
                }
            }
        }

        $coverage = (float) ($hits / count($qt));
        $precision = (float) ($hits / count($ct));
        $best = $coverage === 1.0 ? 0.6 + 0.4 * $precision : 0.0;

        // Tippfehler-Toleranz über die ganze Zeichenkette (Leerzeichen egal).
        $a = str_replace(' ', '', $q);
        $b = str_replace(' ', '', $c);
        $maxLen = max(strlen($a), strlen($b));
        if ($maxLen >= 4 && $maxLen <= 60) {
            $ratio = 1 - levenshtein($a, $b) / $maxLen;
            if ($ratio >= 0.75) {
                $best = max($best, $ratio * 0.9);
            }
        }

        return min($best, 0.99);
    }

    /**
     * Beste Treffer für den Text. Mehrere gleich gute Gerätetypen ("LDX"
     * trifft drei LDX-Varianten) werden als Pool zurückgegeben, damit später
     * aus allen passenden Typen gewählt werden kann.
     *
     * @return array{
     *   kind: ?string, score: float, label: ?string,
     *   geraetetyp_ids: int[], unit_id: ?int, unit_ids: int[], alternatives: array<int, array{label: string, score: float}>
     * }
     */
    public function match(string $query, ?Collection $types = null, ?Collection $units = null): array
    {
        $types ??= Geraetetyp::with('unit')->get();
        $units ??= Unit::all();

        $scored = collect();
        foreach ($types as $type) {
            $scored->push(['kind' => 'typ', 'model' => $type, 'label' => $type->bezeichnung, 'score' => $this->score($query, $type->bezeichnung)]);
        }
        foreach ($units as $unit) {
            // Gruppen minimal schlechter, damit ein gleich guter Gerätetyp gewinnt.
            $scored->push(['kind' => 'gruppe', 'model' => $unit, 'label' => $unit->bezeichnung, 'score' => max(0, $this->score($query, $unit->bezeichnung) - 0.01)]);
        }

        $scored = $scored->filter(fn ($s) => $s['score'] >= self::THRESHOLD)->sortByDesc('score')->values();

        $empty = ['kind' => null, 'score' => 0.0, 'label' => null, 'geraetetyp_ids' => [], 'unit_id' => null, 'unit_ids' => [], 'alternatives' => []];
        if ($scored->isEmpty()) {
            return $empty;
        }

        $top = $scored->first();
        $alternatives = $scored->slice(1, 4)->map(fn ($s) => ['label' => $s['label'], 'score' => round($s['score'], 2)])->values()->all();

        if ($top['kind'] === 'gruppe') {
            return array_merge($empty, [
                'kind' => 'gruppe', 'score' => $top['score'], 'label' => $top['label'],
                'unit_id' => $top['model']->id, 'unit_ids' => [$top['model']->id], 'alternatives' => $alternatives,
            ]);
        }

        // Pool: alle Gerätetypen mit (praktisch) gleicher Trefferqualität.
        $pool = $scored->filter(fn ($s) => $s['kind'] === 'typ' && $s['score'] >= $top['score'] - 0.12)->values();
        $label = $pool->count() > 1
            ? trim($query).' ('.$pool->count().' Typen)'
            : $top['label'];

        return array_merge($empty, [
            'kind' => 'typ', 'score' => $top['score'], 'label' => $label,
            'geraetetyp_ids' => $pool->pluck('model.id')->all(),
            'unit_id' => $top['model']->units_id,
            'unit_ids' => $pool->pluck('model.units_id')->unique()->values()->all(),
            'alternatives' => $alternatives,
        ]);
    }

    /**
     * Zerlegt Freitext in Bedarfszeilen: "3x LDX, 2 Stativ; Optik #21-23".
     * Zeilen/Semikolons trennen immer, Kommas nur in Zeilen ohne "#".
     *
     * @return array<int, array{anzahl: int, query: string, nummern: ?string, raw: string}>
     */
    public function parseLines(string $text): array
    {
        $chunks = preg_split('/[\r\n;]+/', $text) ?: [];
        $lines = [];
        foreach ($chunks as $chunk) {
            $parts = str_contains($chunk, '#') ? [$chunk] : (preg_split('/,/', $chunk) ?: []);
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }

                $anzahl = null;
                $nummern = null;

                if (preg_match('/^(\d{1,3})\s*(?:x|×|\*|stk\.?|stück)?\s+(?=\D)(.+)$/iu', $part, $m) || preg_match('/^(\d{1,3})\s*(?:x|×|\*)\s*(.+)$/iu', $part, $m)) {
                    $anzahl = (int) $m[1];
                    $part = trim($m[2]);
                }
                if (preg_match('/^(.*?)\s*[#№]\s*([\d,\-\s]+)$/u', $part, $m)) {
                    $part = trim($m[1]);
                    $nummern = trim($m[2]);
                }
                if ($anzahl === null && preg_match('/^(.*\D)\s+(\d{1,3})\s*(?:x|×|stk\.?|stück)$/iu', $part, $m)) {
                    $part = trim($m[1]);
                    $anzahl = (int) $m[2];
                }

                if ($part === '') {
                    continue;
                }

                $lines[] = ['anzahl' => max(1, $anzahl ?? 1), 'query' => $part, 'nummern' => $nummern, 'raw' => trim($chunk)];
            }
        }

        return $lines;
    }
}

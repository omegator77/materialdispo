<?php

namespace App\Models;

use App\Models\Concerns\HasReadableActivityDescription;
use App\Services\ItemAssignmentService;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Item extends Model
{
    use HasReadableActivityDescription, LogsActivity {
        HasReadableActivityDescription::getDescriptionForEvent insteadof LogsActivity;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['bezeichnung', 'nummer', 'units_id', 'suppliers_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('item');
    }

    protected function activityNoun(): string
    {
        return 'Gerät';
    }

    protected $fillable = [
        'bezeichnung',
        'nummer',
        'description',
        'units_id',
        'geraetetyp_id',
        'suppliers_id',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'units_id');
    }

    public function geraetetyp()
    {
        return $this->belongsTo(Geraetetyp::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'suppliers_id');
    }

    public function mietvorgaenge()
    {
        return $this->belongsToMany(Mietvorgang::class, 'item_mietvorgang')
            ->withPivot('manual')
            ->withTimestamps();
    }

    /**
     * Archiv der Mietgeräte: das Gerät hatte Mietvorgänge und alle sind abgeschlossen
     * (Rückgabe bestätigt). Ohne Mietvorgang ist ein Gerät nie archiviert. Arbeitet auf
     * der Relation `mietvorgaenge`, die dafür geladen sein sollte.
     */
    public function isRentArchived(): bool
    {
        return $this->mietvorgaenge->isNotEmpty()
            && $this->mietvorgaenge->every(fn (Mietvorgang $mv) => $mv->isComplete());
    }

    /** Ein Mietvorgang ist abgelaufen, aber die Rückgabe ist noch nicht bestätigt. */
    public function hasOpenReturn(): bool
    {
        $heute = now()->startOfDay();

        return $this->mietvorgaenge->contains(
            fn (Mietvorgang $mv) => ! $mv->isComplete() && $mv->rent_end->lt($heute)
        );
    }

    /**
     * Welcher Mietvorgang in der Liste als Zeitraum angezeigt wird: im Archiv der mit dem
     * spätesten Ende; sonst unter den offenen der laufende, dann der nächste kommende,
     * dann der jüngste abgelaufene.
     */
    public function displayMietvorgang(): ?Mietvorgang
    {
        if ($this->mietvorgaenge->isEmpty()) {
            return null;
        }

        if ($this->isRentArchived()) {
            return $this->mietvorgaenge->sortByDesc('rent_end')->first();
        }

        $heute = now()->startOfDay();
        $offen = $this->mietvorgaenge->reject(fn (Mietvorgang $mv) => $mv->isComplete());

        return $offen->filter(fn (Mietvorgang $mv) => $mv->rent_start->lte($heute) && $mv->rent_end->gte($heute))
            ->sortBy('rent_start')->first()
            ?? $offen->filter(fn (Mietvorgang $mv) => $mv->rent_start->gt($heute))->sortBy('rent_start')->first()
            ?? $offen->sortByDesc('rent_end')->first();
    }

    public function vermietvorgaenge()
    {
        return $this->belongsToMany(Vermietvorgang::class, 'item_vermietvorgang')
            ->withPivot('manual')
            ->withTimestamps();
    }

    /**
     * Findet-oder-legt-an den passenden Mietvorgang für (Vermieter, Zeitraum)
     * und fügt eine ZUSÄTZLICHE Zuordnung hinzu (ersetzt keine bestehende) —
     * genutzt von der Geräte-Bearbeitungsseite als Schnellzuordnung. Läuft
     * über dieselbe Konfliktprüfung wie der Mietvorgang-eigene Picker.
     *
     * @return array{added: bool, alreadyAttached: bool, reason: ?string}
     */
    public function syncMietvorgang(string $rentStart, string $rentEnd): array
    {
        if (! $this->suppliers_id) {
            return ['added' => false, 'alreadyAttached' => false, 'reason' => 'Kein Vermieter hinterlegt.'];
        }

        $mietvorgang = Mietvorgang::findOrCreateFor($this->suppliers_id, $rentStart, $rentEnd);

        $result = app(ItemAssignmentService::class)->attachToMietvorgang($this, $mietvorgang, manual: false);

        // Wurde der Vorgang nur für diese Zuordnung angelegt und diese abgelehnt,
        // bleibt sonst ein leerer Vorgang (samt verbrauchtem Referenzcode) zurück.
        if ($mietvorgang->wasRecentlyCreated && ! $result['added']) {
            $mietvorgang->delete();
        }

        return $result;
    }

    /**
     * Findet-oder-legt-an den passenden Vermietvorgang für (Mieter, Zeitraum)
     * und fügt eine ZUSÄTZLICHE Zuordnung hinzu (ersetzt keine bestehende) —
     * genutzt von der Geräte-Bearbeitungsseite als Schnellzuordnung.
     *
     * @return array{added: bool, alreadyAttached: bool, reason: ?string}
     */
    public function syncVermietvorgang(int $mieterId, string $verleihStart, string $verleihEnd): array
    {
        $vermietvorgang = Vermietvorgang::findOrCreateFor($mieterId, $verleihStart, $verleihEnd);

        $result = app(ItemAssignmentService::class)->attachToVermietvorgang($this, $vermietvorgang, manual: false);

        if ($vermietvorgang->wasRecentlyCreated && ! $result['added']) {
            $vermietvorgang->delete();
        }

        return $result;
    }

    public function productions()
    {
        return $this->belongsToMany(Production::class, 'item_production')
            ->withPivot('notes')
            ->withTimestamps();
    }

    public function cameraConfigs()
    {
        return $this->hasMany(CameraConfig::class, 'item_id');
    }

    public function cameraDetail()
    {
        return $this->hasOne(CameraDetail::class);
    }

    public function monitorDetail()
    {
        return $this->hasOne(MonitorDetail::class);
    }

    public function lensDetail()
    {
        return $this->hasOne(LensDetail::class);
    }
}

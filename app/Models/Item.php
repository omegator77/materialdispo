<?php

namespace App\Models;

use App\Models\Concerns\HasReadableActivityDescription;
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
            ->logOnly(['bezeichnung', 'nummer', 'units_id', 'suppliers_id', 'rent_start', 'rent_end', 'mieter_id', 'verleih_start', 'verleih_end'])
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
        'rent_start',
        'rent_end',
        'mietvorgang_id',
        'mietvorgang_manual',
        'mieter_id',
        'verleih_start',
        'verleih_end',
        'vermietvorgang_id',
        'vermietvorgang_manual',
    ];

    protected $casts = [
        'rent_start' => 'date',
        'rent_end' => 'date',
        'mietvorgang_manual' => 'boolean',
        'verleih_start' => 'date',
        'verleih_end' => 'date',
        'vermietvorgang_manual' => 'boolean',
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

    public function mietvorgang()
    {
        return $this->belongsTo(Mietvorgang::class);
    }

    public function mieter()
    {
        return $this->belongsTo(Mieter::class, 'mieter_id');
    }

    public function vermietvorgang()
    {
        return $this->belongsTo(Vermietvorgang::class);
    }

    /**
     * Ordnet das Gerät automatisch einem Mietvorgang zu (gleicher Vermieter +
     * Zeitraum teilen sich einen Vorgang). Greift nicht, wenn das Gerät manuell
     * einem Mietvorgang zugeordnet wurde (sticky) — dort führt der Mietvorgang.
     */
    public function syncMietvorgang(): void
    {
        if ($this->mietvorgang_manual) {
            return;
        }

        if ($this->suppliers_id && $this->rent_start && $this->rent_end) {
            $mietvorgang = Mietvorgang::findOrCreateFor(
                $this->suppliers_id,
                $this->rent_start->format('Y-m-d'),
                $this->rent_end->format('Y-m-d')
            );

            $this->update(['mietvorgang_id' => $mietvorgang->id]);
        } elseif ($this->mietvorgang_id) {
            $this->update(['mietvorgang_id' => null]);
        }
    }

    /**
     * Hebt eine manuelle Mietvorgang-Zuordnung auf und lässt das Gerät wieder
     * in die automatische Vermieter+Zeitraum-Gruppierung zurückfallen.
     */
    public function resetMietvorgangAssignment(): void
    {
        $this->update(['mietvorgang_id' => null, 'mietvorgang_manual' => false]);
        $this->syncMietvorgang();
    }

    /**
     * Entfernt das Gerät vollständig aus der Mietverwaltung (Vermieter,
     * Mietbeginn/-ende und Mietvorgang-Zuordnung werden geleert). Anders als
     * resetMietvorgangAssignment() fällt das Gerät danach NICHT automatisch in
     * eine Gruppe zurück, da es keine eigenen Mietdaten mehr hat — nötig, damit
     * "Entfernen" auf der Mietvorgang-Seite tatsächlich wirkt und ein Mietvorgang
     * ohne Geräte gelöscht werden kann.
     */
    public function removeFromMietvorgang(): void
    {
        $this->update([
            'suppliers_id' => null,
            'rent_start' => null,
            'rent_end' => null,
            'mietvorgang_id' => null,
            'mietvorgang_manual' => false,
        ]);
    }

    /**
     * Ordnet das Gerät automatisch einem Vermietvorgang zu (gleicher Mieter +
     * Zeitraum teilen sich einen Vorgang). Greift nicht, wenn das Gerät manuell
     * einem Vermietvorgang zugeordnet wurde (sticky) — dort führt der Vermietvorgang.
     */
    public function syncVermietvorgang(): void
    {
        if ($this->vermietvorgang_manual) {
            return;
        }

        if ($this->mieter_id && $this->verleih_start && $this->verleih_end) {
            $vermietvorgang = Vermietvorgang::findOrCreateFor(
                $this->mieter_id,
                $this->verleih_start->format('Y-m-d'),
                $this->verleih_end->format('Y-m-d')
            );

            $this->update(['vermietvorgang_id' => $vermietvorgang->id]);
        } elseif ($this->vermietvorgang_id) {
            $this->update(['vermietvorgang_id' => null]);
        }
    }

    /**
     * Hebt eine manuelle Vermietvorgang-Zuordnung auf und lässt das Gerät wieder
     * in die automatische Mieter+Zeitraum-Gruppierung zurückfallen.
     */
    public function resetVermietvorgangAssignment(): void
    {
        $this->update(['vermietvorgang_id' => null, 'vermietvorgang_manual' => false]);
        $this->syncVermietvorgang();
    }

    /**
     * Entfernt das Gerät vollständig aus der Vermietverwaltung (Mieter,
     * Verleih-Beginn/-Ende und Vermietvorgang-Zuordnung werden geleert). Anders
     * als resetVermietvorgangAssignment() fällt das Gerät danach NICHT
     * automatisch in eine Gruppe zurück, da es keine eigenen Verleihdaten mehr
     * hat — nötig, damit "Entfernen" auf der Vermietvorgang-Seite tatsächlich
     * wirkt und ein Vermietvorgang ohne Geräte gelöscht werden kann.
     */
    public function removeFromVermietvorgang(): void
    {
        $this->update([
            'mieter_id' => null,
            'verleih_start' => null,
            'verleih_end' => null,
            'vermietvorgang_id' => null,
            'vermietvorgang_manual' => false,
        ]);
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

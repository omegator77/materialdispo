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
            ->logOnly(['bezeichnung', 'nummer', 'units_id', 'suppliers_id', 'rent_start', 'rent_end'])
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
    ];

    protected $casts = [
        'rent_start' => 'date',
        'rent_end' => 'date',
        'mietvorgang_manual' => 'boolean',
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

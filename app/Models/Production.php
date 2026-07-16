<?php

namespace App\Models;

use App\Models\Concerns\HasReadableActivityDescription;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Production extends Model
{
    use HasReadableActivityDescription, LogsActivity {
        HasReadableActivityDescription::getDescriptionForEvent insteadof LogsActivity;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['bezeichnung', 'booking_start', 'booking_end', 'packlist_notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('production');
    }

    protected function activityNoun(): string
    {
        return 'Produktion';
    }

    protected $fillable = [
        'bezeichnung',
        'booking_start',
        'booking_end',
        'created_at',
        'packlist_notes',
        'packvorgang_confirmed_at',
        'packvorgang_confirmed_by',
        'slack_channel',
        'slack_message_ts',
        'slack_compacted_at',
        // Weitere Spalten, falls benötigt
    ];

    protected $casts = [
        'packvorgang_confirmed_at' => 'datetime',
        'slack_compacted_at' => 'datetime',
    ];

    public function items()
    {

        return $this->belongsToMany(Item::class, 'item_production')
            ->withPivot('notes')
            ->withTimestamps();
    }

    public function cameraConfigs()
    {
        return $this->hasMany(CameraConfig::class, 'production_id');
    }

    public function vbProtokoll()
    {
        return $this->hasOne(VbProtokoll::class);
    }

    public function itemPacks()
    {
        return $this->hasMany(ProductionItemPack::class);
    }

    public function packvorgangConfirmedBy()
    {
        return $this->belongsTo(User::class, 'packvorgang_confirmed_by');
    }

    public function packedItemIds(): \Illuminate\Support\Collection
    {
        return $this->itemPacks->pluck('item_id');
    }

    public function isComplete(): bool
    {
        return $this->packvorgang_confirmed_at !== null;
    }

    /**
     * Zeitpunkt, ab dem der Packvorgang abgeschlossen ist — Basis für die
     * 48h-Kompaktierung der Slack-Nachricht, analog zu Mietvorgang::completedAt().
     */
    public function completedAt(): ?\Carbon\Carbon
    {
        return $this->packvorgang_confirmed_at;
    }

    /**
     * Zweistufiger Zuordnungsstatus für die Slack-Ampel: ohne VB-Protokoll
     * bzw. ohne Anforderungen darin gibt es noch nichts abzugleichen (grau);
     * mit Anforderungen entscheidet VbProtokoll::abgleich(), ob alle durch
     * zugeordnete Geräte gedeckt sind (rot = offene Positionen, grün = vollständig).
     */
    public function assignmentStatus(): array
    {
        if (! $this->vbProtokoll) {
            return ['level' => 'grau', 'label' => 'Kein VB-Protokoll angelegt'];
        }

        if ($this->vbProtokoll->anforderungen->isEmpty()) {
            return ['level' => 'grau', 'label' => 'Keine Anforderungen im VB-Protokoll'];
        }

        $offen = $this->vbProtokoll->abgleich()->where('erfuellt', false)->count();

        return $offen > 0
            ? ['level' => 'rot', 'label' => "Zuordnung unvollständig ({$offen} offen)"]
            : ['level' => 'gruen', 'label' => 'Zuordnung vollständig'];
    }

    /**
     * Vereinheitlichte Soll-Geräteliste: Einzelgeräte + alle Slots aus
     * Kamera-Konfigurationen, dedupliziert nach Gerät. Einzige Quelle der
     * Wahrheit für Abgleich, Packvorgang-Ansicht und Packvorgang-PDF.
     */
    public function packlistEntries(): \Illuminate\Support\Collection
    {
        $entries = collect();

        foreach ($this->items as $item) {
            $entries->push([
                'item' => $item,
                'source' => 'einzel',
                'role' => null,
                'config_id' => null,
                'cam_number' => null,
                'notes' => $item->pivot->notes,
            ]);
        }

        foreach ($this->cameraConfigs as $config) {
            $slots = [
                'Kamera' => $config->item,
                'Objektiv' => $config->lensItem,
                'Stativ' => $config->tripodItem,
                'Stativkopf' => $config->headItem,
                'Adapter' => $config->adapterItem,
            ];

            foreach ($slots as $rolle => $item) {
                if (! $item) {
                    continue;
                }

                $entries->push([
                    'item' => $item,
                    'source' => 'kamera',
                    'role' => $rolle,
                    'config_id' => $config->id,
                    'cam_number' => $config->cam_number,
                    'notes' => $config->notes,
                ]);
            }
        }

        return $entries->unique(fn ($entry) => $entry['item']->id)->values();
    }
}

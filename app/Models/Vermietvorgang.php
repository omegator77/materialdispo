<?php

namespace App\Models;

use App\Models\Concerns\HasReadableActivityDescription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Vermietvorgang extends Model
{
    use HasReadableActivityDescription, LogsActivity {
        HasReadableActivityDescription::getDescriptionForEvent insteadof LogsActivity;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'mieter_id',
                'rent_start',
                'rent_end',
                'transport_type_start',
                'transport_type_end',
                'notify_mieter',
                'reminder_days_before_start',
                'reminder_days_before_end',
                'mailing_list_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('vermietvorgang');
    }

    protected function activityNoun(): string
    {
        return 'Vermietvorgang';
    }

    protected function activityLabel(): string
    {
        $mieter = $this->mieter?->bezeichnung ?? 'unbekannter Mieter';
        $start = $this->rent_start ? \Carbon\Carbon::parse($this->rent_start)->format('d.m.Y') : '?';
        $end = $this->rent_end ? \Carbon\Carbon::parse($this->rent_end)->format('d.m.Y') : '?';

        return "{$mieter}, {$start}–{$end}";
    }

    protected $table = 'vermietvorgaenge';

    protected $fillable = [
        'mieter_id',
        'rent_start',
        'rent_end',
        'transport_type_start',
        'transport_type_end',
        'notify_mieter',
        'reminder_days_before_start',
        'reminder_days_before_end',
        'mailing_list_id',
        'transport_start_confirmed_at',
        'transport_start_confirmed_by',
        'transport_end_confirmed_at',
        'transport_end_confirmed_by',
        'gerichtet_confirmed_at',
        'gerichtet_confirmed_by',
    ];

    protected $casts = [
        'rent_start' => 'date',
        'rent_end' => 'date',
        'notify_mieter' => 'boolean',
        'transport_start_confirmed_at' => 'datetime',
        'transport_end_confirmed_at' => 'datetime',
        'gerichtet_confirmed_at' => 'datetime',
    ];

    const TRANSPORT_TYPES_START = [
        'kurier' => 'Kurier',
        'wir_liefern' => 'Wir liefern',
        'mieter_holt_ab' => 'Mieter holt ab',
    ];

    const TRANSPORT_TYPES_END = [
        'kurier' => 'Kurier',
        'wir_holen_ab' => 'Wir holen ab',
        'mieter_bringt_zurueck' => 'Mieter bringt zurück',
    ];

    public function mieter()
    {
        return $this->belongsTo(Mieter::class, 'mieter_id');
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function reminderLogs()
    {
        return $this->hasMany(VermietvorgangReminderLog::class);
    }

    public function mailingList()
    {
        return $this->belongsTo(MailingList::class);
    }

    public function transportStartConfirmedBy()
    {
        return $this->belongsTo(User::class, 'transport_start_confirmed_by');
    }

    public function transportEndConfirmedBy()
    {
        return $this->belongsTo(User::class, 'transport_end_confirmed_by');
    }

    public function isTransportConfirmed(string $type): bool
    {
        return $this->{"transport_{$type}_confirmed_at"} !== null;
    }

    public function gerichtetConfirmedBy()
    {
        return $this->belongsTo(User::class, 'gerichtet_confirmed_by');
    }

    public function isGerichtet(): bool
    {
        return $this->gerichtet_confirmed_at !== null;
    }

    public function effectiveReminderDaysBeforeStart(): int
    {
        return $this->reminder_days_before_start ?? config('reminders.default_days_before');
    }

    public function effectiveReminderDaysBeforeEnd(): int
    {
        return $this->reminder_days_before_end ?? config('reminders.default_days_before');
    }

    /**
     * Findet einen bestehenden Vermietvorgang für (Mieter, Zeitraum) oder legt
     * einen neuen an, damit mehrere Geräte desselben Mieters/Zeitraums sich
     * einen Vermietvorgang teilen (eine Konfiguration, eine Erinnerungsmail).
     */
    public static function findOrCreateFor(int $mieterId, string $rentStart, string $rentEnd): self
    {
        return static::firstOrCreate([
            'mieter_id' => $mieterId,
            'rent_start' => $rentStart,
            'rent_end' => $rentEnd,
        ]);
    }

    /**
     * Alle Productions, denen mindestens eines der Items dieses Vermietvorgangs
     * zugeordnet ist (dedupliziert). Dient nur noch als Kontext für die
     * Erinnerungsmail ("wird benötigt für") — die Fristberechnung selbst
     * läuft über die eigenen reminder_days_before_*-Felder.
     */
    public function relatedProductions(): Collection
    {
        return $this->items()
            ->with('productions')
            ->get()
            ->pluck('productions')
            ->flatten()
            ->unique('id')
            ->values();
    }
}

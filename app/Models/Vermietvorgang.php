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
                'bezeichnung',
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
        return $this->bezeichnung ?: $this->fallbackLabel();
    }

    private function fallbackLabel(): string
    {
        $mieter = $this->mieter?->bezeichnung ?? 'unbekannter Mieter';
        $start = $this->rent_start ? \Carbon\Carbon::parse($this->rent_start)->format('d.m.Y') : '?';
        $end = $this->rent_end ? \Carbon\Carbon::parse($this->rent_end)->format('d.m.Y') : '?';

        return "{$mieter}, {$start}–{$end}";
    }

    protected $table = 'vermietvorgaenge';

    protected $fillable = [
        'bezeichnung',
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
        'vollstaendig_zurueck_confirmed_at',
        'vollstaendig_zurueck_confirmed_by',
        'slack_channel',
        'slack_message_ts',
        'slack_compacted_at',
    ];

    protected $casts = [
        'rent_start' => 'date',
        'rent_end' => 'date',
        'notify_mieter' => 'boolean',
        'transport_start_confirmed_at' => 'datetime',
        'transport_end_confirmed_at' => 'datetime',
        'gerichtet_confirmed_at' => 'datetime',
        'vollstaendig_zurueck_confirmed_at' => 'datetime',
        'slack_compacted_at' => 'datetime',
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

    /**
     * Vermietvorgang = Geräte gehen raus: beim Hinweg wird an den Mieter
     * übergeben, beim Rückweg vom Mieter wieder angenommen.
     */
    public function transportActionLabel(string $type): string
    {
        return $type === 'start' ? 'Übergeben' : 'Angenommen';
    }

    public function gerichtetConfirmedBy()
    {
        return $this->belongsTo(User::class, 'gerichtet_confirmed_by');
    }

    public function isGerichtet(): bool
    {
        return $this->gerichtet_confirmed_at !== null;
    }

    public function vollstaendigZurueckConfirmedBy()
    {
        return $this->belongsTo(User::class, 'vollstaendig_zurueck_confirmed_by');
    }

    public function isVollstaendigZurueck(): bool
    {
        return $this->vollstaendig_zurueck_confirmed_at !== null;
    }

    /**
     * Vorgang gilt als abgeschlossen, wenn die Geräte vom Mieter zurück
     * angenommen wurden UND als vollständig zurück bestätigt sind. Bestimmt,
     * ob die Slack-Nachricht noch Buttons zeigt oder als final gerendert wird.
     */
    public function isComplete(): bool
    {
        return $this->isTransportConfirmed('end') && $this->isVollstaendigZurueck();
    }

    /**
     * Zeitpunkt, ab dem der Vorgang tatsächlich abgeschlossen ist — der
     * spätere der beiden für isComplete() nötigen Zeitstempel. Null, solange
     * der Vorgang noch nicht abgeschlossen ist.
     */
    public function completedAt(): ?\Carbon\Carbon
    {
        if (! $this->isComplete()) {
            return null;
        }

        return $this->transport_end_confirmed_at->greaterThan($this->vollstaendig_zurueck_confirmed_at)
            ? $this->transport_end_confirmed_at
            : $this->vollstaendig_zurueck_confirmed_at;
    }

    public function effectiveReminderDaysBeforeStart(): int
    {
        return (int) ($this->reminder_days_before_start
            ?? Setting::get('reminder_days_before_start')
            ?? config('reminders.default_days_before'));
    }

    public function effectiveReminderDaysBeforeEnd(): int
    {
        return (int) ($this->reminder_days_before_end
            ?? Setting::get('reminder_days_before_end')
            ?? config('reminders.default_days_before'));
    }

    /**
     * Findet einen bestehenden Vermietvorgang für (Mieter, Zeitraum) oder legt
     * einen neuen an, damit mehrere Geräte desselben Mieters/Zeitraums sich
     * einen Vermietvorgang teilen (eine Konfiguration, eine Erinnerungsmail).
     */
    public static function findOrCreateFor(int $mieterId, string $rentStart, string $rentEnd): self
    {
        return static::firstOrCreate(
            [
                'mieter_id' => $mieterId,
                'rent_start' => $rentStart,
                'rent_end' => $rentEnd,
            ],
            ['bezeichnung' => static::suggestBezeichnung($mieterId)]
        );
    }

    /**
     * Vorgeschlagene Bezeichnung für einen neuen Vermietvorgang: Mieter-Name
     * + Referenzcode (z. B. "Jens V-260108") — bleibt nach der Anlage frei
     * editierbar, ist also nur ein Startwert.
     */
    public static function suggestBezeichnung(int $mieterId): string
    {
        $mieterName = Mieter::find($mieterId)?->bezeichnung ?? 'Unbekannt';

        return "{$mieterName} ".static::nextReferenceCode();
    }

    /**
     * Fortlaufender Referenzcode "V-JJMMNN" — NN zählt pro Kalendermonat der
     * Anlage (created_at), nicht pro Mieter, damit der Code allein schon
     * eindeutig ist.
     */
    private static function nextReferenceCode(): string
    {
        $now = now();
        $seq = static::whereYear('created_at', $now->year)->whereMonth('created_at', $now->month)->count() + 1;

        return 'V-'.$now->format('ym').sprintf('%02d', $seq);
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

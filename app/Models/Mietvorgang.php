<?php

namespace App\Models;

use App\Models\Concerns\HasReadableActivityDescription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Mietvorgang extends Model
{
    use HasReadableActivityDescription, LogsActivity {
        HasReadableActivityDescription::getDescriptionForEvent insteadof LogsActivity;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'bezeichnung',
                'suppliers_id',
                'rent_start',
                'rent_end',
                'transport_type_start',
                'transport_type_end',
                'notify_supplier',
                'reminder_days_before_start',
                'reminder_days_before_end',
                'mailing_list_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('mietvorgang');
    }

    protected function activityNoun(): string
    {
        return 'Mietvorgang';
    }

    protected function activityLabel(): string
    {
        return $this->bezeichnung ?: $this->fallbackLabel();
    }

    private function fallbackLabel(): string
    {
        $supplier = $this->supplier?->bezeichnung ?? 'unbekannter Vermieter';
        $start = $this->rent_start ? \Carbon\Carbon::parse($this->rent_start)->format('d.m.Y') : '?';
        $end = $this->rent_end ? \Carbon\Carbon::parse($this->rent_end)->format('d.m.Y') : '?';

        return "{$supplier}, {$start}–{$end}";
    }

    protected $table = 'mietvorgaenge';

    protected $fillable = [
        'bezeichnung',
        'suppliers_id',
        'rent_start',
        'rent_end',
        'transport_type_start',
        'transport_type_end',
        'notify_supplier',
        'reminder_days_before_start',
        'reminder_days_before_end',
        'mailing_list_id',
        'transport_start_confirmed_at',
        'transport_start_confirmed_by',
        'transport_end_confirmed_at',
        'transport_end_confirmed_by',
        'kontrolliert_confirmed_at',
        'kontrolliert_confirmed_by',
        'bereit_zur_rueckgabe_confirmed_at',
        'bereit_zur_rueckgabe_confirmed_by',
        'slack_channel',
        'slack_message_ts',
        'slack_compacted_at',
    ];

    protected $casts = [
        'rent_start' => 'date',
        'rent_end' => 'date',
        'notify_supplier' => 'boolean',
        'transport_start_confirmed_at' => 'datetime',
        'transport_end_confirmed_at' => 'datetime',
        'kontrolliert_confirmed_at' => 'datetime',
        'bereit_zur_rueckgabe_confirmed_at' => 'datetime',
        'slack_compacted_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'suppliers_id');
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'item_mietvorgang')
            ->withPivot('manual')
            ->withTimestamps();
    }

    public function reminderLogs()
    {
        return $this->hasMany(MietvorgangReminderLog::class);
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
     * Mietvorgang = Geräte kommen rein: beim Hinweg wird vom Vermieter
     * angenommen, beim Rückweg an den Vermieter wieder übergeben.
     */
    public function transportActionLabel(string $type): string
    {
        return $type === 'start' ? 'Angenommen' : 'Übergeben';
    }

    public function kontrolliertConfirmedBy()
    {
        return $this->belongsTo(User::class, 'kontrolliert_confirmed_by');
    }

    public function isKontrolliert(): bool
    {
        return $this->kontrolliert_confirmed_at !== null;
    }

    public function bereitZurRueckgabeConfirmedBy()
    {
        return $this->belongsTo(User::class, 'bereit_zur_rueckgabe_confirmed_by');
    }

    public function isBereitZurRueckgabe(): bool
    {
        return $this->bereit_zur_rueckgabe_confirmed_at !== null;
    }

    /**
     * Vorgang gilt als abgeschlossen, wenn die Geräte an den Vermieter
     * übergeben wurden UND als bereit zur Rückgabe bestätigt sind — das
     * Pendant zu Vermietvorgang::isComplete() (dort "vollständig zurück"),
     * "kontrolliert" ist dagegen der Eingangs-Check beim Hinweg. Bestimmt, ob
     * die Slack-Nachricht noch Buttons zeigt oder als final gerendert wird.
     */
    public function isComplete(): bool
    {
        return $this->isTransportConfirmed('end') && $this->isBereitZurRueckgabe();
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

        return $this->transport_end_confirmed_at->greaterThan($this->bereit_zur_rueckgabe_confirmed_at)
            ? $this->transport_end_confirmed_at
            : $this->bereit_zur_rueckgabe_confirmed_at;
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
     * Findet einen bestehenden Mietvorgang für (Vermieter, Zeitraum) oder legt
     * einen neuen an, damit mehrere Geräte desselben Vermieters/Zeitraums sich
     * einen Mietvorgang teilen (eine Konfiguration, eine Erinnerungsmail).
     */
    public static function findOrCreateFor(int $suppliersId, string $rentStart, string $rentEnd): self
    {
        return static::firstOrCreate(
            [
                'suppliers_id' => $suppliersId,
                'rent_start' => $rentStart,
                'rent_end' => $rentEnd,
            ],
            ['bezeichnung' => static::suggestBezeichnung($suppliersId)]
        );
    }

    /**
     * Vorgeschlagene Bezeichnung für einen neuen Mietvorgang: Vermieter-Name
     * + Referenzcode (z. B. "Pixmade M-260108") — bleibt nach der Anlage frei
     * editierbar, ist also nur ein Startwert.
     */
    public static function suggestBezeichnung(int $suppliersId): string
    {
        $supplierName = Supplier::find($suppliersId)?->bezeichnung ?? 'Unbekannt';

        return "{$supplierName} ".static::nextReferenceCode();
    }

    /**
     * Fortlaufender Referenzcode "M-JJMMNN" — NN zählt pro Kalendermonat der
     * Anlage (created_at), nicht pro Vermieter, damit der Code allein schon
     * eindeutig ist.
     */
    private static function nextReferenceCode(): string
    {
        $now = now();
        $seq = static::whereYear('created_at', $now->year)->whereMonth('created_at', $now->month)->count() + 1;

        return 'M-'.$now->format('ym').sprintf('%02d', $seq);
    }

    /**
     * Alle Productions, denen mindestens eines der Items dieses Mietvorgangs
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

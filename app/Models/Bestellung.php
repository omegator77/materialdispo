<?php

namespace App\Models;

use App\Models\Concerns\HasReadableActivityDescription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Bestellung extends Model
{
    use HasReadableActivityDescription, LogsActivity {
        HasReadableActivityDescription::getDescriptionForEvent insteadof LogsActivity;
    }

    public const STUFE_BESTELLT = 'bestellt';

    public const STUFE_UNTERWEGS = 'unterwegs';

    public const STUFE_RECHNUNG = 'rechnung';

    public const STUFE_ABGELEGT = 'abgelegt';

    public const CARRIERS = [
        'dhl' => 'DHL',
        'dpd' => 'DPD',
        'ups' => 'UPS',
        'gls' => 'GLS',
        'hermes' => 'Hermes',
        'sonstige' => 'Sonstige',
    ];

    public const TRACKING_URLS = [
        'dhl' => 'https://www.dhl.de/de/privatkunden/pakete-empfangen/verfolgen.html?piececode={nummer}',
        'dpd' => 'https://tracking.dpd.de/parcelstatus?query={nummer}&locale=de_DE',
        'ups' => 'https://www.ups.com/track?tracknum={nummer}',
        'gls' => 'https://gls-group.com/DE/de/paketverfolgung?match={nummer}',
        'hermes' => 'https://www.myhermes.de/empfangen/sendungsverfolgung/sendungsinformation/#{nummer}',
    ];

    protected $table = 'bestellungen';

    protected $fillable = [
        'bestellt_bei',
        'bestellt_was',
        'bezahlt_mit',
        'bestellt_am',
        'created_by',
        'versanddienstleister',
        'sendungsnummer',
        'rechnung_vorhanden_at',
        'rechnung_vorhanden_by',
        'abgelegt_at',
        'abgelegt_by',
        'angekommen_at',
        'angekommen_by',
        'angekommen_notiz',
        'letzte_erinnerung_at',
        'slack_channel',
        'slack_message_ts',
        'slack_view_id',
    ];

    protected function casts(): array
    {
        return [
            'bestellt_am' => 'datetime',
            'rechnung_vorhanden_at' => 'datetime',
            'abgelegt_at' => 'datetime',
            'angekommen_at' => 'datetime',
            'letzte_erinnerung_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'bestellt_bei', 'bestellt_was', 'bezahlt_mit', 'bestellt_am',
                'versanddienstleister', 'sendungsnummer',
                'rechnung_vorhanden_at', 'abgelegt_at',
                'angekommen_at', 'angekommen_notiz',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('bestellung');
    }

    protected function activityNoun(): string
    {
        return 'Bestellung';
    }

    protected function activityLabel(): string
    {
        return (string) $this->bestellt_bei;
    }

    public function besteller()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rechnungVon()
    {
        return $this->belongsTo(User::class, 'rechnung_vorhanden_by');
    }

    public function abgelegtVon()
    {
        return $this->belongsTo(User::class, 'abgelegt_by');
    }

    public function angekommenVon()
    {
        return $this->belongsTo(User::class, 'angekommen_by');
    }

    public function scopeOffen(Builder $query): Builder
    {
        return $query->whereNull('abgelegt_at');
    }

    public function scopeAbgelegt(Builder $query): Builder
    {
        return $query->whereNotNull('abgelegt_at');
    }

    public function stufe(): string
    {
        if ($this->abgelegt_at) {
            return self::STUFE_ABGELEGT;
        }
        if ($this->rechnung_vorhanden_at) {
            return self::STUFE_RECHNUNG;
        }
        if ($this->sendungsnummer) {
            return self::STUFE_UNTERWEGS;
        }

        return self::STUFE_BESTELLT;
    }

    public function stufeLabel(): string
    {
        return match ($this->stufe()) {
            self::STUFE_ABGELEGT => 'Bezahlt & abgelegt',
            self::STUFE_RECHNUNG => 'Rechnung liegt vor',
            self::STUFE_UNTERWEGS => 'Unterwegs',
            default => 'Bestellt',
        };
    }

    public function trackingUrl(): ?string
    {
        $pattern = self::TRACKING_URLS[$this->versanddienstleister ?? ''] ?? null;

        if ($pattern === null || ! $this->sendungsnummer) {
            return null;
        }

        return str_replace('{nummer}', rawurlencode($this->sendungsnummer), $pattern);
    }

    public function markRechnungVorhanden(User $by): bool
    {
        $affected = activity()->withoutLogs(fn () => static::whereKey($this->id)
            ->whereNull('rechnung_vorhanden_at')
            ->whereNull('abgelegt_at')
            ->update(['rechnung_vorhanden_at' => now(), 'rechnung_vorhanden_by' => $by->id]));

        $this->refresh();

        if ($affected !== 1) {
            return false;
        }

        $this->logStage($by, 'rechnung_vorhanden', 'Rechnung liegt vor');

        return true;
    }

    public function markAbgelegt(User $by): bool
    {
        $affected = activity()->withoutLogs(fn () => static::whereKey($this->id)
            ->whereNull('abgelegt_at')
            ->update(['abgelegt_at' => now(), 'abgelegt_by' => $by->id]));

        $this->refresh();

        if ($affected !== 1) {
            return false;
        }

        $this->logStage($by, 'abgelegt', 'als bezahlt & abgelegt markiert');

        return true;
    }

    public function istAngekommen(): bool
    {
        return $this->angekommen_at !== null;
    }

    public function markAngekommen(User $by, ?string $notiz = null): bool
    {
        $notiz = trim((string) $notiz);
        $notiz = $notiz === '' ? null : mb_substr($notiz, 0, 255);

        $affected = activity()->withoutLogs(fn () => static::whereKey($this->id)
            ->whereNull('angekommen_at')
            ->update([
                'angekommen_at' => now(),
                'angekommen_by' => $by->id,
                'angekommen_notiz' => $notiz,
            ]));

        $this->refresh();

        if ($affected !== 1) {
            return false;
        }

        $this->logStage($by, 'angekommen', 'Sendung angekommen'.($notiz !== null ? " (Notiz: {$notiz})" : ''));

        return true;
    }

    public function revertAngekommen(User $by): bool
    {
        $affected = activity()->withoutLogs(fn () => static::whereKey($this->id)
            ->whereNotNull('angekommen_at')
            ->update(['angekommen_at' => null, 'angekommen_by' => null, 'angekommen_notiz' => null]));

        $this->refresh();

        if ($affected !== 1) {
            return false;
        }

        $this->logStage($by, 'angekommen_zurueckgenommen', 'Ankunft zurückgenommen');

        return true;
    }

    public function revertStage(User $by): void
    {
        if ($this->abgelegt_at) {
            activity()->withoutLogs(fn () => $this->forceFill(['abgelegt_at' => null, 'abgelegt_by' => null])->save());
            $this->logStage($by, 'abgelegt_zurueckgenommen', 'Stufe "Bezahlt & abgelegt" zurückgenommen');
        } elseif ($this->rechnung_vorhanden_at) {
            activity()->withoutLogs(fn () => $this->forceFill(['rechnung_vorhanden_at' => null, 'rechnung_vorhanden_by' => null])->save());
            $this->logStage($by, 'rechnung_zurueckgenommen', 'Stufe "Rechnung liegt vor" zurückgenommen');
        }
    }

    private function logStage(User $by, string $event, string $text): void
    {
        activity('bestellung')
            ->performedOn($this)
            ->causedBy($by)
            ->event($event)
            ->log("Bestellung \"{$this->bestellt_bei}\": {$text}");
    }
}

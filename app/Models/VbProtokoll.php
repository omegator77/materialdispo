<?php

namespace App\Models;

use App\Models\Concerns\HasReadableActivityDescription;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class VbProtokoll extends Model
{
    use LogsActivity, HasReadableActivityDescription {
        HasReadableActivityDescription::getDescriptionForEvent insteadof LogsActivity;
    }

    protected $table = 'vb_protokolle';

    protected $fillable = [
        'production_id',
        'created_by',
        'kunde',
        'produktionsort',
        'crew_ul',
        'crew_bt_sng',
        'crew_ti',
        'crew_sng',
        'crew_bt_dl',
        'crew_tt',
        'crew_tl',
        'crew_ba',
        'crew_ta',
        'crew_kabelhilfen',
        'crew_kamera',
        'crew_evs',
        'besonderheiten',
        'kabelwege',
        'audio_mic',
        'audio_inear',
        'audio_kommplatz',
        'isdn_funk',
        'maz_evs_usb',
        'monitore',
        'sonstiges',
        'zeitplan',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['kunde', 'produktionsort'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('vb_protokoll');
    }

    protected function activityNoun(): string
    {
        return 'VB-Protokoll';
    }

    protected function activityLabel(): string
    {
        return $this->production?->bezeichnung ?? '';
    }

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function anforderungen()
    {
        return $this->hasMany(VbProtokollAnforderung::class);
    }

    public function fotos()
    {
        return $this->hasMany(VbProtokollFoto::class);
    }

    /**
     * Soll/Ist-Abgleich: pro Anforderung die tatsächlich gepackte Anzahl ermitteln.
     */
    public function abgleich(): \Illuminate\Support\Collection
    {
        return $this->anforderungen->map(function (VbProtokollAnforderung $anforderung) {
            if ($anforderung->cam_number !== null) {
                $komponenten = collect([
                    $anforderung->geraetetyp?->bezeichnung,
                    $anforderung->lensGeraetetyp ? 'Objektiv: '.$anforderung->lensGeraetetyp->bezeichnung : null,
                    $anforderung->tripodGeraetetyp ? 'Stativ: '.$anforderung->tripodGeraetetyp->bezeichnung : null,
                    $anforderung->tripodHeadGeraetetyp ? 'Kopf: '.$anforderung->tripodHeadGeraetetyp->bezeichnung : null,
                    $anforderung->adapterGeraetetyp ? 'Adapter: '.$anforderung->adapterGeraetetyp->bezeichnung : null,
                ])->filter()->implode(', ');

                return [
                    'label' => 'Kamera '.$anforderung->cam_number,
                    'kind' => 'kamera',
                    'benoetigt' => null,
                    'gepackt' => null,
                    'erfuellt' => null,
                    'notiz' => trim(collect([$komponenten, $anforderung->notiz])->filter()->implode(' — ')),
                ];
            }

            if ($anforderung->freitext) {
                return [
                    'label' => $anforderung->freitext,
                    'kind' => 'frei',
                    'benoetigt' => $anforderung->anzahl,
                    'gepackt' => null,
                    'erfuellt' => null,
                    'notiz' => $anforderung->notiz,
                ];
            }

            if ($anforderung->geraetetyp_id) {
                $gepackt = $this->production->items()
                    ->where('items.geraetetyp_id', $anforderung->geraetetyp_id)
                    ->count();

                $label = $anforderung->geraetetyp?->bezeichnung ?? '—';
                $kind = 'typ';
            } else {
                $gepackt = $this->production->items()
                    ->where('items.units_id', $anforderung->unit_id)
                    ->count();

                $label = $anforderung->unit?->bezeichnung ?? '—';
                $kind = 'gruppe';
            }

            return [
                'label' => $label,
                'kind' => $kind,
                'benoetigt' => $anforderung->anzahl,
                'gepackt' => $gepackt,
                'erfuellt' => $gepackt >= $anforderung->anzahl,
                'notiz' => $anforderung->notiz,
            ];
        });
    }
}

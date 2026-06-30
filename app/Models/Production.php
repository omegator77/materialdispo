<?php

namespace App\Models;

use App\Models\Concerns\HasReadableActivityDescription;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Production extends Model
{
    use LogsActivity, HasReadableActivityDescription {
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
        // Weitere Spalten, falls benötigt
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
}

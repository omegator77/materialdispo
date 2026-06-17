<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'bezeichnung',
        'nummer',
        'description',
        'units_id',
        'suppliers_id',
        'rent_start',
        'rent_end',
    ];

    protected $casts = [
        'rent_start' => 'date',
        'rent_end' => 'date',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'units_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'suppliers_id');
    }

    public function productions()
    {
        return $this->belongsToMany(Production::class, 'item_production');
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
}
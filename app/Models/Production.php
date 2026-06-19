<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Production extends Model
{

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
}

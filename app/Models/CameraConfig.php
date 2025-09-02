<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CameraConfig extends Model
{
    use HasFactory;

    // Tabelle, falls der Name von der Konvention abweicht (z. B. wenn du eine andere Tabelle verwendest):
    // protected $table = 'camera_configs';

    /**
     * Die Felder, die massenweise zuweisbar sind.
     */
    protected $fillable = [
        'production_id',
        'item_id',
        'cam_number',
        'cam_position',
        'lens',
        'tripod',
        'tripod_head',
        'large_lens_adapter',
        'notes',
    ];

    /**
     * Beziehung zur `Production`-Tabelle.
     */
    public function production()
    {
        return $this->belongsTo(Production::class, 'production_id');
    }

    // app/Models/CameraConfig.php
public function item()       { return $this->belongsTo(\App\Models\Item::class, 'item_id'); }
public function lensItem()   { return $this->belongsTo(\App\Models\Item::class, 'lens'); }
public function tripodItem() { return $this->belongsTo(\App\Models\Item::class, 'tripod'); }
public function headItem()   { return $this->belongsTo(\App\Models\Item::class, 'tripod_head'); }
public function adapItem()   { return $this->belongsTo(\App\Models\Item::class, 'large_lens_adapter'); }

}

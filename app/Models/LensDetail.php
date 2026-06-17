<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LensDetail extends Model
{
    protected $fillable = [
        'item_id',
        'manufacturer',
        'model',
        'serial_number',
        'zoom_factor',
        'zoom_servo_model',
        'zoom_servo_serial_number',
        'focus_servo_model',
        'focus_servo_serial_number',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}

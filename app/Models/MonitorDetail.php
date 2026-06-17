<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitorDetail extends Model
{
    protected $fillable = [
        'item_id',
        'manufacturer',
        'model',
        'serial_number',
        'screen_size',
        'has_speakers',
        'has_headphone',
        'converter_number',
        'converter_model',
        'converter_audio',
        'max_input_format',
        'has_stand',
        'stand_number',
    ];

    protected $casts = [
        'has_speakers' => 'boolean',
        'has_headphone' => 'boolean',
        'converter_audio' => 'boolean',
        'has_stand' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
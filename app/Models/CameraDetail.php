<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CameraDetail extends Model
{
    protected $fillable = [
        'item_id',
        'body_serial',
        'fiber_adapter_serial',
        'large_viewfinder_type',
        'large_viewfinder_serial',
        'small_viewfinder_type',
        'small_viewfinder_serial',
        'ssl_license',
        'large_viewfinder_model',
        'small_viewfinder_model',
    ];

    protected $casts = [
        'ssl_license' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
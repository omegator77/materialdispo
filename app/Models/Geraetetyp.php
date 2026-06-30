<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Geraetetyp extends Model
{
    protected $table = 'geraetetypen';

    protected $fillable = [
        'units_id',
        'bezeichnung',
        'description',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'units_id');
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}

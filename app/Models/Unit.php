<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = [
        'bezeichnung',
        'description',
    ];

    public function items()
    {
        return $this->hasMany(Item::class, 'units_id');
    }
}

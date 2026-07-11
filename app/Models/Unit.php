<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = [
        'bezeichnung',
        'description',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::creating(function (Unit $unit) {
            $unit->sort_order ??= (static::max('sort_order') ?? 0) + 1;
        });
    }

    public function items()
    {
        return $this->hasMany(Item::class, 'units_id');
    }
}

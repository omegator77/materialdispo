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

    /**
     * Einheitliche Anzeige-Reihenfolge für Gruppen: erst die auf der
     * Gruppenseite konfigurierte sort_order, dann alphabetisch als Fallback.
     * Diese eine Definition gilt appweit (Dropdowns, Listen, Timeline), damit
     * die Reihenfolge nicht in jedem Controller neu erfunden wird.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('bezeichnung');
    }

    public function items()
    {
        return $this->hasMany(Item::class, 'units_id');
    }
}

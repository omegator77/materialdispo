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

    /**
     * Sortiert Gerätetypen nach der Anzeige-Reihenfolge ihrer Gruppe
     * (Unit::sort_order, "ohne Gruppe" ans Ende), dann alphabetisch. Ersetzt
     * das frühere orderBy('units_id'), das nur der Anlagereihenfolge folgte.
     */
    public function scopeOrderedByUnit($query)
    {
        $unitSortOrder = Unit::select('sort_order')
            ->whereColumn('units.id', 'geraetetypen.units_id');

        return $query
            ->orderByRaw('('.$unitSortOrder->toSql().') is null')
            ->orderBy($unitSortOrder)
            ->orderBy('bezeichnung');
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}

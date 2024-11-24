<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    public function unit() {   
        return $this->belongsTo(Unit::class, 'units_id');
        }

    public function supplier() {   
        return $this->belongsTo(Supplier::class, 'suppliers_id');
        }    

    public function productions()
        {
       
          return $this->belongsToMany(Production::class, 'item_production');
        }    

        protected $fillable = [
            'bezeichnung',
            'description',
            'units_id',
            'suppliers_id',
            'is_rented',
            'rent_start', 
            'rent_end',
        ];
}

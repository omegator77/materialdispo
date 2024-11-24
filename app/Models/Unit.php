<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    public function items () {  
        return $this->hasMany(Item::class, 'units_id');
        }

    protected $fillable = [
         'bezeichnung',
         'description'
     ];
}

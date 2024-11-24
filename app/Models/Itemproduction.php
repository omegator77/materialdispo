<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Itemproduction extends Model
{
    protected $table = 'item_production';

 // Beziehung zur Production
    public function production()
    {
        return $this->belongsTo(Production::class, 'production_id');
    }
// Beziehung zum Item
    public function item()
{
    return $this->belongsTo(Item::class, 'item_id');
}
}

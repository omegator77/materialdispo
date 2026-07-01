<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Itemproduction extends Model
{
    protected $table = 'item_production';

    public function production()
    {
        return $this->belongsTo(Production::class, 'production_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionItemPack extends Model
{
    protected $fillable = [
        'production_id',
        'item_id',
        'packed_by',
        'packed_at',
    ];

    protected $casts = [
        'packed_at' => 'datetime',
    ];

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function packedByUser()
    {
        return $this->belongsTo(User::class, 'packed_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'bezeichnung',
        'kontakt',
        'phone',
        'email',
    ];

    public function items()
    {
        return $this->hasMany(Item::class, 'suppliers_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mieter extends Model
{
    use HasFactory;

    protected $table = 'mieter';

    protected $fillable = [
        'bezeichnung',
        'kontakt',
        'phone',
        'email',
    ];

    public function items()
    {
        return $this->hasMany(Item::class, 'mieter_id');
    }

    public function vermietvorgaenge()
    {
        return $this->hasMany(Vermietvorgang::class, 'mieter_id');
    }
}

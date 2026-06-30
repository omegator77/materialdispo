<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VbProtokollKamera extends Model
{
    protected $table = 'vb_protokoll_kameras';

    protected $fillable = [
        'vb_protokoll_id',
        'position',
        'bezeichnung',
    ];

    public function vbProtokoll()
    {
        return $this->belongsTo(VbProtokoll::class);
    }
}

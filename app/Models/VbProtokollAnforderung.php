<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VbProtokollAnforderung extends Model
{
    protected $table = 'vb_protokoll_anforderungen';

    protected $fillable = [
        'vb_protokoll_id',
        'unit_id',
        'geraetetyp_id',
        'anzahl',
        'notiz',
    ];

    public function vbProtokoll()
    {
        return $this->belongsTo(VbProtokoll::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function geraetetyp()
    {
        return $this->belongsTo(Geraetetyp::class);
    }
}

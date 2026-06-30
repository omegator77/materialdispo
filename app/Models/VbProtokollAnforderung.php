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
        'freitext',
        'anzahl',
        'notiz',
        'cam_number',
        'lens_geraetetyp_id',
        'tripod_geraetetyp_id',
        'tripod_head_geraetetyp_id',
        'adapter_geraetetyp_id',
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

    public function lensGeraetetyp()
    {
        return $this->belongsTo(Geraetetyp::class, 'lens_geraetetyp_id');
    }

    public function tripodGeraetetyp()
    {
        return $this->belongsTo(Geraetetyp::class, 'tripod_geraetetyp_id');
    }

    public function tripodHeadGeraetetyp()
    {
        return $this->belongsTo(Geraetetyp::class, 'tripod_head_geraetetyp_id');
    }

    public function adapterGeraetetyp()
    {
        return $this->belongsTo(Geraetetyp::class, 'adapter_geraetetyp_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VbProtokollFreitextBlock extends Model
{
    protected $table = 'vb_protokoll_freitext_bloecke';

    protected $fillable = [
        'vb_protokoll_id',
        'ueberschrift',
        'text',
        'sort_order',
    ];

    public function vbProtokoll()
    {
        return $this->belongsTo(VbProtokoll::class);
    }
}

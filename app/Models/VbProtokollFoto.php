<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class VbProtokollFoto extends Model
{
    protected $table = 'vb_protokoll_fotos';

    protected $fillable = [
        'vb_protokoll_id',
        'path',
        'original_name',
    ];

    public function vbProtokoll()
    {
        return $this->belongsTo(VbProtokoll::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}

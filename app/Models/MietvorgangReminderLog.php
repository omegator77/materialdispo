<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MietvorgangReminderLog extends Model
{
    protected $table = 'mietvorgang_reminder_logs';

    protected $fillable = [
        'mietvorgang_id',
        'reminder_type',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function mietvorgang()
    {
        return $this->belongsTo(Mietvorgang::class);
    }
}

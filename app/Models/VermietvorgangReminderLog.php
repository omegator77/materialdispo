<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VermietvorgangReminderLog extends Model
{
    protected $table = 'vermietvorgang_reminder_logs';

    protected $fillable = [
        'vermietvorgang_id',
        'reminder_type',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function vermietvorgang()
    {
        return $this->belongsTo(Vermietvorgang::class);
    }
}

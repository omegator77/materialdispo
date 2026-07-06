<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DryHireReminderLog extends Model
{
    protected $fillable = [
        'dry_hire_id',
        'reminder_type',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function dryHire()
    {
        return $this->belongsTo(DryHire::class);
    }
}

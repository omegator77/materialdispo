<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DryHire extends Model
{
    protected $fillable = [
        'production_id',
        'delivery_type',
        'return_type',
        'customer_email',
        'notify_customer',
        'reminder_days_before_start',
        'reminder_days_before_end',
        'mailing_list_id',
        'start_confirmed_at',
        'start_confirmed_by',
        'end_confirmed_at',
        'end_confirmed_by',
    ];

    protected $casts = [
        'notify_customer' => 'boolean',
        'start_confirmed_at' => 'datetime',
        'end_confirmed_at' => 'datetime',
    ];

    const DELIVERY_TYPES = [
        'kunde_holt_ab' => 'Kunde holt ab',
        'wir_liefern' => 'Wir liefern',
        'kurier' => 'Kurier',
    ];

    const RETURN_TYPES = [
        'kunde_bringt_zurueck' => 'Kunde bringt zurück',
        'wir_holen_ab' => 'Wir holen ab',
        'kurier' => 'Kurier',
    ];

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function mailingList()
    {
        return $this->belongsTo(MailingList::class);
    }

    public function startConfirmedBy()
    {
        return $this->belongsTo(User::class, 'start_confirmed_by');
    }

    public function endConfirmedBy()
    {
        return $this->belongsTo(User::class, 'end_confirmed_by');
    }

    public function reminderLogs()
    {
        return $this->hasMany(DryHireReminderLog::class);
    }

    public function isTransportConfirmed(string $type): bool
    {
        return $this->{"{$type}_confirmed_at"} !== null;
    }

    public function effectiveReminderDaysBeforeStart(): int
    {
        return $this->reminder_days_before_start ?? config('reminders.default_days_before');
    }

    public function effectiveReminderDaysBeforeEnd(): int
    {
        return $this->reminder_days_before_end ?? config('reminders.default_days_before');
    }
}

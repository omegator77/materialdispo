<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailingListRecipient extends Model
{
    protected $fillable = [
        'mailing_list_id',
        'name',
        'email',
    ];

    public function mailingList()
    {
        return $this->belongsTo(MailingList::class);
    }
}

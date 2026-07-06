<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailingList extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function recipients()
    {
        return $this->hasMany(MailingListRecipient::class);
    }

    public function mietvorgaenge()
    {
        return $this->hasMany(Mietvorgang::class, 'mailing_list_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailingList extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function recipients()
    {
        return $this->hasMany(MailingListRecipient::class);
    }

    public function mietvorgaenge()
    {
        return $this->hasMany(Mietvorgang::class, 'mailing_list_id');
    }

    /**
     * Legt diese Liste als einzige Standardliste fest (hebt den Status bei
     * allen anderen auf), damit immer höchstens eine Liste "Standard" ist.
     */
    public function makeDefault(): void
    {
        static::where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }
}

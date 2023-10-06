<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChequeTransaction extends Model
{
    protected $guarded = [];

    public function cheque()
    {
        return $this->belongsTo(Cheque::class);
    }

}
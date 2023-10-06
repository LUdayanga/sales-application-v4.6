<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cheque extends Model
{
    protected $guarded = [];

    public function cheque_transactions()
    {
        return $this->hasMany(ChequeTransaction::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function transaction_payments()
    {
        return $this->hasMany(TransactionPayment::class);
    }

}
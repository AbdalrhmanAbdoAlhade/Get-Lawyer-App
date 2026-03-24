<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = ['case_id', 'user_id', 'amount', 'payment_gateway_id', 'type'];

    public function legalCase()
    {
        return $this->belongsTo(LegalCase::class);
    }
}
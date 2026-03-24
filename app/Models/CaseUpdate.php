<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseUpdate extends Model
{
    protected $fillable = ['case_id', 'user_id', 'message', 'attachment'];

    public function legalCase()
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
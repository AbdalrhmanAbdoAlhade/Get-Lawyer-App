<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'client_id',
        'provider_id',
        'rating',
        'comment'
    ];

    /**
     * العلاقة مع القضية (التقييم ينتمي لقضية واحدة)
     */
    public function legalCase()
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    /**
     * العلاقة مع العميل (الذي كتب التقييم)
     */
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * العلاقة مع المحامي (الذي تم تقييمه)
     */
    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = ['case_id', 'provider_id', 'attachments', 'offered_price', 'proposal_text'];

    protected $casts = [
        'attachments' => 'array',
    ];
    
    public function getAttachmentUrlsAttribute()
    {
        if (!$this->attachments) return [];
        return collect($this->attachments)->map(function ($path) {
            return asset('storage/' . $path);
        });
    }
    
    protected $appends = ['attachment_urls'];

    public function legalCase()
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}

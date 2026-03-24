<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalCase extends Model
{
    protected $fillable = [
        'client_id',
        'title',
        'description',
        'category',
        'initial_budget',
        'status',
        'attachments',
        'accepted_provider_id'
    ];

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

public static function getCategories()
    {
        return [
            'Criminal',
            'Civil',
            'Commercial',
            'Family Law',
            'Administrative',
            'Labor',
            'Real Estate',
    'Financial'
        ];
    }
    
protected $appends = ['attachment_urls'];

    // العميل صاحب القضية
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    // المحامي اللي تم قبوله للشغل
    public function acceptedProvider()
    {
        return $this->belongsTo(User::class, 'accepted_provider_id');
    }

    // العروض المقدمة على هذه القضية
    public function offers()
    {
        return $this->hasMany(Offer::class, 'case_id');
    }

    // تحديثات القضية (السجل اللي شفناه في الصورة)
    public function updates()
    {
        return $this->hasMany(CaseUpdate::class, 'case_id');
    }
}

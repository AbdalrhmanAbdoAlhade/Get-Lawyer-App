<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role', 
        'profile_image',
        'is_active'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // دمج جميع الحقول المحسوبة هنا
    protected $appends = [
        'profile_image_url', 
        'average_rating', 
        'reviews_count'
    ];

   protected $with = ['providerProfile']; // سيتم جلب البروفايل دائماً مع بيانات المحامي

    // --- Accessors (حسابات تلقائية) ---

    // رابط الصورة الشخصية
    public function getProfileImageUrlAttribute()
    {
        return $this->profile_image 
            ? asset('storage/' . $this->profile_image) 
            : asset('images/default-avatar.png'); 
    }

    // تقييم المحامي
    public function getAverageRatingAttribute()
    {
        return round($this->reviewsReceived()->avg('rating') ?: 0, 1);
    }

    // عدد التقييمات
    public function getReviewsCountAttribute()
    {
        return $this->reviewsReceived()->count();
    }

    // --- العلاقات (Relationships) ---

    public function providerProfile()
    {
        return $this->hasOne(ProviderProfile::class);
    }

    public function cases()
    {
        return $this->hasMany(LegalCase::class, 'client_id');
    }

    public function offers()
    {
        return $this->hasMany(Offer::class, 'provider_id');
    }
    
    public function reviewsReceived()
    {
        return $this->hasMany(Review::class, 'provider_id');
    }
    
        public function conversationsAsClient()
    {
        return $this->hasMany(Conversation::class, 'client_id');
    }
    
    public function conversationsAsLawyer()
    {
        return $this->hasMany(Conversation::class, 'lawyer_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderProfile extends Model
{
    protected $fillable = [
        'user_id',
        'provider_type',
        'id_photo',
        'license_photo',
        'personal_photo',
        'iban',
        'status',
        'admin_notes'
    ];

    // إضافة الروابط تلقائياً عند طلب البروفايل
    protected $appends = [
        'id_photo_url', 
        'license_photo_url', 
        'personal_photo_url'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessors لروابط الصور
    public function getIdPhotoUrlAttribute() {
        return $this->id_photo ? asset('storage/' . $this->id_photo) : null;
    }

    public function getLicensePhotoUrlAttribute() {
        return $this->license_photo ? asset('storage/' . $this->license_photo) : null;
    }

    public function getPersonalPhotoUrlAttribute() {
        return $this->personal_photo ? asset('storage/' . $this->personal_photo) : null;
    }
}
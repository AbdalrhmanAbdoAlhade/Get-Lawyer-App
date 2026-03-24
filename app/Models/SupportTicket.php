<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'problem_type',
        'title',
        'description',
        'status',
    ];

    public static array $problemTypes = [
        'technical'  => 'مشكلة تقنية',
        'financial'  => 'مشكلة مالية',
        'legal'      => 'مشكلة قانونية',
        'other'      => 'أخرى',
    ];

    // --- Relationships ---
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attachments()
    {
        return $this->hasMany(SupportTicketAttachment::class);
    }
}
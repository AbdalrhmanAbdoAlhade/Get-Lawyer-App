<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['client_id', 'lawyer_id', 'last_message_at'];

    public function client()   { return $this->belongsTo(User::class, 'client_id'); }
    public function lawyer()   { return $this->belongsTo(User::class, 'lawyer_id'); }
    public function messages() { return $this->hasMany(Message::class); }
    public function lastMessage() { return $this->hasOne(Message::class)->latestOfMany(); }

    public function otherParticipant(int $authId): User
    {
        return $this->client_id === $authId ? $this->lawyer : $this->client;
    }

    public function unreadCount(int $authId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $authId)
            ->whereNull('read_at')
            ->count();
    }
}
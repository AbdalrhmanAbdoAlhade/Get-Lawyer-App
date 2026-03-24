<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Message extends Model
{
    protected $fillable = ['conversation_id', 'sender_id', 'body', 'read_at'];
    protected $casts    = ['read_at' => 'datetime'];
    protected $appends  = ['is_read'];

    public function getIsReadAttribute(): bool
    {
        return !is_null($this->read_at);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
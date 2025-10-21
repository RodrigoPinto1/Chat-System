<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'recipient_id',
        'content',
        'meta',
        'type',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    // Encrypt content before saving, decrypt when reading
    public function setContentAttribute($value)
    {
        $this->attributes['content'] = encrypt($value);
    }

    public function getContentAttribute($value)
    {
        return decrypt($value);
    }
}

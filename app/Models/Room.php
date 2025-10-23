<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'avatar',
        'name',
        'reference',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps()->withPivot(['role', 'joined_at', 'last_read_at']);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}

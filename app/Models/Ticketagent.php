<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Ticketagent extends Model
{
    use HasFactory, Notifiable;

    protected $fillable =
    [
        'user_id',
        'ticketinstance_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticketinstance()
    {
        return $this->belongsTo(Ticketinstance::class);
    }
}

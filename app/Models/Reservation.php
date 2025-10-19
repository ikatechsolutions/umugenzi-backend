<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ticket_id',
        'quantite',
        'name', 
        'email',
        'phone',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function ticketinstances()
    {
        return $this->hasMany(Ticketinstance::class);
    }
}

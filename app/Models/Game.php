<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory, Notifiable;

    protected $fillable =
    [
        'groupe_id',
        'candidat',
        'phone',
        'gift_id',
    ];

    public function groupe()
    {
        return $this->belongsTo(Groupe::class);
    }

    public function routeNotificationForTwilio()
    {
        return $this->phone;
    }

    public function gift()
    {
        return $this->belongsTo(Gift::class);
    }
}

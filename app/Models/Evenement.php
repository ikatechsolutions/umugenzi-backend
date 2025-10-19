<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evenement extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'description',
        'place',
        'date_event',
        'heure',
        'image',
        'user_id',
        'categorie_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categorie()
    {
        return $this->belongsTo(Category::class);
    }

    public function typetickets()
    {
        return $this->hasMany(Typeticket::class);
    }
}

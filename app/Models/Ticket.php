<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'quantite',
        'typeticket_id',
    ];

    public function typeticket()
    {
        return $this->belongsTo(Typeticket::class);
    }
}

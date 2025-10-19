<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Typeticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'prix',
        'evenement_id',
    ];

    public function evenement()
    {
        return $this->belongsTo(Evenement::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}

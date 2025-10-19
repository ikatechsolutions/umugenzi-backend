<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Groupe extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = 'Groupe' . Carbon::now()->format('Y-m-d_H-i-s');
    }

    public function games()
    {
        return $this->hasMany(Game::class);
    }
}

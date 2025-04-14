<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username', 'fullname', 'phone_number', 'email', 'password', 'otp',
    ];

    protected $hidden = [
        'password',
    ];

    // Relasi ke resep yang disukai user
    public function favoriteRecipes()
    {
        return $this->belongsToMany(Recipe::class, 'favorites');
    }
}

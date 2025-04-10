<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject; 
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Model implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'users'; 
    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    // JWT required methods
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}

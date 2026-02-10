<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'slug',      // ← lägg till
        'country',   // ← lägg till
        'gender',    // ← lägg till
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($user) {

            // skapa slug automatiskt om saknas
            if (empty($user->slug)) {

                $base = Str::slug($user->name) ?: 'user';

                // säkerställ unik slug
                do {
                    $slug = $base . '-' . Str::lower(Str::random(6));
                } while (self::where('slug', $slug)->exists());

                $user->slug = $slug;
            }

        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adventure extends Model
{
    protected $fillable = [
        'user_id',
        'start_date',
        'location',
        'kilograms',
        'adventure_text',
        'types',
        // ✅ ta bort 'photos' (det är relation, inte kolumn)
    ];

    protected $casts = [
        'start_date' => 'date',
        'types' => 'array',
        // ✅ ta bort 'photos' cast
        'kilograms' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function photos()
    {
        return $this->hasMany(\App\Models\AdventurePhoto::class)->orderBy('sort');
    }

    // första uppladdade bilden (sort 0)
    public function coverPhoto()
    {
        return $this->hasOne(\App\Models\AdventurePhoto::class)->orderBy('sort');
    }
}

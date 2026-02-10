<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdventurePhoto extends Model
{
    protected $fillable = [
        'adventure_id',
        'path',
        'sort',
    ];

    public function adventure()
    {
        return $this->belongsTo(Adventure::class);
    }
}

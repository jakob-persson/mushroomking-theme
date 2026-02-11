<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Intervention\Image\Laravel\Facades\Image;


class AdventurePhoto extends Model
{
    protected $fillable = [
    'adventure_id',
    'path',
    'feed_path',
    'sort',
    ];

    public function adventure()
    {
        return $this->belongsTo(Adventure::class);
    }
    
}



<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(\App\Observers\StoryObserver::class)]
class Story extends Model
{
    use SoftDeletes;
    
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }
}
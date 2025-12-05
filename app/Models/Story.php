<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kirschbaum\Commentions\Contracts\Commentable;
use Kirschbaum\Commentions\HasComments;

#[ObservedBy(\App\Observers\StoryObserver::class)]
class Story extends Model implements Commentable
{
    use SoftDeletes;
    use HasComments;

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id', 'id');
    }
}

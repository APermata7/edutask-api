<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = [
        'submission_id',
        'lecturer_id',
        'name',
        'score',
        'feedback',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }
}

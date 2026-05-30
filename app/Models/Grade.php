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

    // app/Models/Grade.php
    public function submission()
    {
        return $this->belongsTo(Submission::class, 'submission_id');
    }

    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }
}

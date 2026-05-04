<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Assignments\Models\Assignment;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'student_id',
        'content',
        'file_path',
        'submitted_at',
        'status',
        'grade',
        'feedback',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function gradeRecord()
    {
        return $this->hasOne(Grade::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }
}

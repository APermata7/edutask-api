<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use App\Modules\Assignments\Models\Assignment;

#[Fillable(['name', 'description', 'max_students', 'status', 'lecturer_id'])]
class ClassRoom extends Model
{
    use HasFactory;

    protected $table = 'classes';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate invite code when creating
        static::creating(function ($model) {
            if (empty($model->invite_code)) {
                $model->invite_code = Str::random(8);
            }
        });
    }

    /**
     * Get the lecturer who owns this class
     */
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    /**
     * Get the enrollments for this class
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    /**
     * Get the students enrolled in this class
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'enrollments',
            'class_id',
            'student_id'
        )
        ->withTimestamps()
        ->withPivot('status', 'enrolled_at');
    }

    /**
     * Check if a student is enrolled in this class
     */
    public function hasStudent(int $studentId): bool
    {
        return $this->enrollments()
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Get the count of enrolled students
     */
    public function getEnrolledCountAttribute(): int
    {
        return $this->enrollments()
            ->where('status', 'active')
            ->count();
    }

    /**
     * Check if class is at capacity
     */
    public function isFull(): bool
    {
        if (is_null($this->max_students)) {
            return false;
        }

        return $this->getEnrolledCountAttribute() >= $this->max_students;
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'class_id');
    }
}

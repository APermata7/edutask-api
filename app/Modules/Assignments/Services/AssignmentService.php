<?php

namespace App\Modules\Assignments\Services;

use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\User;
use App\Modules\Assignments\Models\Assignment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AssignmentService
{
    public function paginateForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Assignment::query()->with(['lecturer:id,name,email', 'classroom:id,name,lecturer_id']);

        if ($user->isLecturer()) {
            $query->where('lecturer_id', $user->id);
        } else {
            $enrolledClassIds = Enrollment::query()
                ->where('student_id', $user->id)
                ->where('status', 'active')
                ->pluck('class_id');

            $query->where('status', Assignment::STATUS_PUBLISHED)
                  ->whereIn('class_id', $enrolledClassIds);
        }

        $this->applyFilters($query, $filters);

        return $query->latest()->paginate($perPage);
    }

    public function paginateByClassForUser(int $classId, User $user, int $perPage = 15): LengthAwarePaginator
    {
        $class = ClassRoom::query()->findOrFail($classId);

        $query = Assignment::query()
            ->with(['lecturer:id,name,email', 'classroom:id,name,lecturer_id'])
            ->where('class_id', $classId);

        if ($user->isLecturer()) {
            if ($class->lecturer_id !== $user->id) {
                throw new AuthorizationException('Anda tidak berhak mengakses kelas ini.');
            }
        } else {
            $isEnrolled = Enrollment::query()
                ->where('class_id', $classId)
                ->where('student_id', $user->id)
                ->where('status', 'active')
                ->exists();

            if (!$isEnrolled) {
                throw new AuthorizationException('Anda tidak terdaftar di kelas ini.');
            }

            $query->where('status', Assignment::STATUS_PUBLISHED);
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(array $data, User $user): Assignment
    {
        $class = ClassRoom::query()
            ->whereKey($data['class_id'])
            ->where('lecturer_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        $data['class_id'] = $class->id;
        $data['lecturer_id'] = $user->id;
        $data['status'] = Assignment::STATUS_DRAFT;

        return Assignment::create($data)->loadMissing(['lecturer', 'classroom']);
    }

    public function update(Assignment $assignment, array $data): Assignment
    {
        $assignment->fill($data)->save();

        return $assignment->refresh()->loadMissing(['lecturer', 'classroom']);
    }

    public function publish(Assignment $assignment): Assignment
    {
        if ($assignment->status !== Assignment::STATUS_PUBLISHED) {
            $assignment->status = Assignment::STATUS_PUBLISHED;
            $assignment->published_at ??= now();
            $assignment->save();
        }

        return $assignment->refresh()->loadMissing(['lecturer', 'classroom']);
    }

    public function delete(Assignment $assignment): void
    {
        $assignment->delete();
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function (Builder $subQuery) use ($search) {
                $subQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('instructions', 'like', "%{$search}%");
            });
        }
    }
}
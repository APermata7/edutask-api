<?php

namespace App\Modules\Assignments\Services;

use App\Models\User;
use App\Modules\Assignments\Models\Assignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AssignmentService
{
    public function paginateForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Assignment::query()->with(['lecturer:id,name,email']);

        if ($user->isLecturer()) {
            $query->where('lecturer_id', $user->id);
        } else {
            $query->where('status', Assignment::STATUS_PUBLISHED);

            // tambahkan filter lagi agar mahasiswa hanya melihat assignment dari kelas yang diikuti
        }

        $this->applyFilters($query, $filters);

        return $query->latest()->paginate($perPage);
    }

    public function paginateByClassForUser(int $classId, User $user, int $perPage = 15): LengthAwarePaginator
    {
        $query = Assignment::query()
            ->with(['lecturer:id,name,email'])
            ->where('class_id', $classId);

        if ($user->isLecturer()) {
            $query->where('lecturer_id', $user->id);
        } else {
            $query->where('status', Assignment::STATUS_PUBLISHED);
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(array $data, User $user): Assignment
    {
        $data['lecturer_id'] = $user->id;
        $data['status'] = Assignment::STATUS_DRAFT;

        return Assignment::create($data);
    }

    public function update(Assignment $assignment, array $data): Assignment
    {
        $assignment->fill($data)->save();

        return $assignment->refresh();
    }

    public function publish(Assignment $assignment): Assignment
    {
        if ($assignment->status !== Assignment::STATUS_PUBLISHED) {
            $assignment->status = Assignment::STATUS_PUBLISHED;
            $assignment->published_at ??= now();
            $assignment->save();
        }

        return $assignment->refresh();
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
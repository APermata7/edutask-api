<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Grade::with(['submission.assignment.classroom', 'submission.student', 'lecturer']);

        if ($user->role === 'student') {
            $query->whereHas('submission', function ($submissionQuery) use ($user) {
                $submissionQuery->where('student_id', $user->id);
            });
        }

        if ($user->role === 'lecturer') {
            $query->whereHas('submission.assignment.classroom', function ($classQuery) use ($user) {
                $classQuery->where('lecturer_id', $user->id);
            });
        }

        if ($request->has('submission_id')) {
            $query->where('submission_id', $request->submission_id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar grade',
            'data' => $query->latest()->get(),
        ]);
    }

    public function show($id)
    {
        $grade = Grade::with(['submission.assignment.classroom', 'submission.student', 'lecturer'])->find($id);

        if (!$grade) {
            return response()->json([
                'success' => false,
                'message' => 'Grade not found'
            ], 404);
        }

        if (!$this->canAccessGrade($grade)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak punya akses'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail grade',
            'data' => $grade,
        ]);
    }

    public function store(Request $request, $classId = null)
    {
        $validated = $request->validate([
            'submission_id' => 'required|exists:submissions,id',
            'score' => 'required|integer|min:0|max:100',
            'feedback' => 'nullable|string',
        ]);

        $submission = Submission::with(['assignment.classroom', 'student'])->findOrFail($validated['submission_id']);

        if ($classId && (int) $classId !== (int) $submission->assignment->class_id) {
            return response()->json([
                'success' => false,
                'message' => 'Submission tidak berada di kelas ini'
            ], 422);
        }

        if (!$this->canGradeSubmission($submission)) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya dosen pemilik kelas yang bisa memberi nilai'
            ], 403);
        }

        $grade = Grade::updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'lecturer_id' => auth()->id(),
                'name' => $submission->student->name,
                'score' => $validated['score'],
                'feedback' => $validated['feedback'] ?? null,
            ]
        );

        $submission->update([
            'grade' => $validated['score'],
            'feedback' => $validated['feedback'] ?? $submission->feedback,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Grade created',
            'data' => $grade->load(['submission.assignment.classroom', 'submission.student', 'lecturer'])
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $grade = Grade::with('submission.assignment.classroom')->find($id);

        if (!$grade) {
            return response()->json([
                'success' => false,
                'message' => 'Grade not found'
            ], 404);
        }

        if (!$this->canAccessGrade($grade) || auth()->user()->role !== 'lecturer') {
            return response()->json([
                'success' => false,
                'message' => 'Tidak punya akses'
            ], 403);
        }

        $validated = $request->validate([
            'score' => 'sometimes|required|integer|min:0|max:100',
            'feedback' => 'sometimes|nullable|string',
        ]);

        $grade->update($validated);

        $submissionUpdates = [];
        if (array_key_exists('score', $validated)) {
            $submissionUpdates['grade'] = $validated['score'];
        }
        if (array_key_exists('feedback', $validated)) {
            $submissionUpdates['feedback'] = $validated['feedback'];
        }
        if ($submissionUpdates) {
            $grade->submission->update($submissionUpdates);
        }

        return response()->json([
            'success' => true,
            'message' => 'Grade updated',
            'data' => $grade->fresh(['submission.assignment.classroom', 'submission.student', 'lecturer'])
        ]);
    }

    public function destroy($id)
    {
        $grade = Grade::with('submission.assignment.classroom')->find($id);

        if (!$grade) {
            return response()->json([
                'success' => false,
                'message' => 'Grade not found'
            ], 404);
        }

        if (!$this->canAccessGrade($grade) || auth()->user()->role !== 'lecturer') {
            return response()->json([
                'success' => false,
                'message' => 'Tidak punya akses'
            ], 403);
        }

        $grade->submission->update([
            'grade' => null,
            'feedback' => null,
        ]);

        $grade->delete();

        return response()->json([
            'success' => true,
            'message' => 'Grade deleted'
        ]);
    }

    private function canGradeSubmission(Submission $submission): bool
    {
        $user = auth()->user();

        return $user
            && $user->role === 'lecturer'
            && (int) $submission->assignment->classroom->lecturer_id === (int) $user->id;
    }

    private function canAccessGrade(Grade $grade): bool
    {
        $user = auth()->user();

        if (!$user || !$grade->submission) {
            return false;
        }

        if ($user->role === 'student') {
            return (int) $grade->submission->student_id === (int) $user->id;
        }

        return $user->role === 'lecturer'
            && (int) $grade->submission->assignment->classroom->lecturer_id === (int) $user->id;
    }
}

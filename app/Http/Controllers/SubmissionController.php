<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Modules\Assignments\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Submission::with(['assignment.classroom', 'student', 'gradeRecord.lecturer', 'feedbacks.user']);

        if ($user->role === 'student') {
            $query->where('student_id', $user->id);
        }

        if ($request->has('assignment_id')) {
            $query->where('assignment_id', $request->assignment_id);
        }

        $submissions = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar submission',
            'data' => $submissions
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya mahasiswa yang bisa mengumpulkan tugas'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'assignment_id' => 'required|exists:assignments,id',
            'content' => 'nullable|string',
            'file' => 'nullable|file|max:5120|mimes:pdf,doc,docx,zip,jpg,jpeg,png'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $assignment = Assignment::find($request->assignment_id);
        $classroom = $assignment->classroom;

        if (!$classroom->hasStudent($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak terdaftar di kelas ini'
            ], 403);
        }

        $existing = Submission::where('assignment_id', $assignment->id)
                              ->where('student_id', $user->id)
                              ->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah mengumpulkan tugas ini'
            ], 409);
        }

        $isLate = now()->gt($assignment->due_at);
        $status = $isLate ? 'late' : 'submitted';

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('submissions', 'public');
        }

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $user->id,
            'content' => $request->content,
            'file_path' => $filePath,
            'submitted_at' => now(),
            'status' => $status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil dikumpulkan',
            'data' => $submission->load('assignment.classroom', 'student', 'gradeRecord.lecturer', 'feedbacks.user')
        ], 201);
    }

    public function show($id)
    {
        $submission = Submission::with(['assignment.classroom', 'student', 'gradeRecord.lecturer', 'feedbacks.user'])->find($id);
        if (!$submission) {
            return response()->json([
                'success' => false,
                'message' => 'Submission tidak ditemukan'
            ], 404);
        }

        $user = auth()->user();
        $isLecturer = ($user->role === 'lecturer' && $submission->assignment->classroom->lecturer_id === $user->id);
        $isOwner = ($submission->student_id === $user->id);

        if (!$isLecturer && !$isOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak punya akses'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail submission',
            'data' => $submission->load('assignment.classroom', 'student', 'gradeRecord.lecturer', 'feedbacks.user')
        ]);
    }

    public function update(Request $request, $id)
    {
        $submission = Submission::find($id);
        if (!$submission) {
            return response()->json([
                'success' => false,
                'message' => 'Submission tidak ditemukan'
            ], 404);
        }

        $user = auth()->user();
        $isOwner = ($submission->student_id === $user->id);

        // Hanya mahasiswa pemilik yang boleh resubmit (update)
        if ($isOwner) {
            $validator = Validator::make($request->all(), [
                'content' => 'nullable|string',
                'file' => 'nullable|file|max:5120|mimes:pdf,doc,docx,zip,jpg,jpeg,png'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->hasFile('file')) {
                if ($submission->file_path) {
                    Storage::disk('public')->delete($submission->file_path);
                }
                $submission->file_path = $request->file('file')->store('submissions', 'public');
            }
            if ($request->has('content')) {
                $submission->content = $request->content;
            }
            $submission->status = 'resubmitted';
            $submission->submitted_at = now();
            $submission->save();

            return response()->json([
                'success' => true,
                'message' => 'Tugas berhasil direvisi',
                'data' => $submission
            ]);
        }

        // Jika bukan pemilik (misalnya dosen), tolak
        return response()->json([
            'success' => false,
            'message' => 'Tidak punya hak akses'
        ], 403);
    }

    public function destroy($id)
    {
        $submission = Submission::find($id);
        if (!$submission) {
            return response()->json([
                'success' => false,
                'message' => 'Submission tidak ditemukan'
            ], 404);
        }

        $user = auth()->user();
        $isLecturer = ($user->role === 'lecturer' && $submission->assignment->classroom->lecturer_id === $user->id);

        if (!$isLecturer) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak punya hak akses'
            ], 403);
        }

        if ($submission->file_path) {
            Storage::disk('public')->delete($submission->file_path);
        }
        $submission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Submission berhasil dihapus'
        ]);
    }
}

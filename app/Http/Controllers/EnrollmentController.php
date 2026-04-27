<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class EnrollmentController extends Controller
{
    /**
     * Get all students in a class (lecturer only, must be owner)
     */
    public function index($classId)
    {
        $user = JWTAuth::user();
        $class = ClassRoom::find($classId);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Kelas tidak ditemukan',
            ], 404);
        }

        // Validasi ownership
        if ($class->lecturer_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak melihat daftar siswa kelas ini',
            ], 403);
        }

        $enrollments = Enrollment::where('class_id', $classId)
            ->with('student')
            ->get()
            ->map(function ($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'student' => [
                        'id' => $enrollment->student->id,
                        'name' => $enrollment->student->name,
                        'email' => $enrollment->student->email,
                    ],
                    'status' => $enrollment->status,
                    'enrolled_at' => $enrollment->enrolled_at,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Daftar siswa di kelas',
            'data' => [
                'class_id' => $class->id,
                'class_name' => $class->name,
                'total_students' => $enrollments->count(),
                'students' => $enrollments,
            ],
        ]);
    }

    /**
     * Manually enroll a student (lecturer only, must be owner)
     */
    public function store(Request $request, $classId)
    {
        $user = JWTAuth::user();
        $class = ClassRoom::find($classId);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Kelas tidak ditemukan',
            ], 404);
        }

        // Validasi ownership
        if ($class->lecturer_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak menambahkan siswa ke kelas ini',
            ], 403);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
        ]);

        $studentId = $validated['student_id'];

        // Check if user exists and is a student
        $student = \App\Models\User::find($studentId);
        if ($student->role !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'User bukan siswa',
            ], 400);
        }

        // Check if already enrolled
        $existingEnrollment = Enrollment::where('class_id', $classId)
            ->where('student_id', $studentId)
            ->first();

        if ($existingEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Siswa sudah terdaftar di kelas ini',
            ], 409);
        }

        // Check if class is full
        if ($class->isFull()) {
            return response()->json([
                'success' => false,
                'message' => 'Kelas sudah penuh, tidak dapat menambahkan siswa',
            ], 409);
        }

        // Create enrollment
        $enrollment = Enrollment::create([
            'class_id' => $classId,
            'student_id' => $studentId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Siswa berhasil ditambahkan ke kelas',
            'data' => [
                'enrollment_id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'class_id' => $enrollment->class_id,
                'status' => $enrollment->status,
            ],
        ], 201);
    }

    /**
     * Remove a student from a class (lecturer only, must be owner)
     */
    public function destroy($classId, $enrollmentId)
    {
        $user = JWTAuth::user();
        $class = ClassRoom::find($classId);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Kelas tidak ditemukan',
            ], 404);
        }

        // Validasi ownership
        if ($class->lecturer_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak menghapus siswa dari kelas ini',
            ], 403);
        }

        $enrollment = Enrollment::find($enrollmentId);

        if (!$enrollment || $enrollment->class_id != $classId) {
            return response()->json([
                'success' => false,
                'message' => 'Pendaftaran siswa tidak ditemukan',
            ], 404);
        }

        $enrollment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Siswa berhasil dihapus dari kelas',
        ]);
    }

    /**
     * Get enrollment status (student can check their own, lecturer can check any in their class)
     */
    public function show($classId)
    {
        $user = JWTAuth::user();
        $class = ClassRoom::find($classId);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Kelas tidak ditemukan',
            ], 404);
        }

        $enrollment = Enrollment::where('class_id', $classId)
            ->where('student_id', $user->id)
            ->first();

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak terdaftar di kelas ini',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status pendaftaran',
            'data' => [
                'class_id' => $enrollment->class_id,
                'student_id' => $enrollment->student_id,
                'status' => $enrollment->status,
                'enrolled_at' => $enrollment->enrolled_at,
            ],
        ]);
    }

    /**
     * Check if a specific student is enrolled in a class
     */
    public function checkEnrollment($classId, $studentId)
    {
        $user = JWTAuth::user();
        $class = ClassRoom::find($classId);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Kelas tidak ditemukan',
            ], 404);
        }

        // Validasi ownership (lecturer) atau student checking themselves
        if ($class->lecturer_id !== $user->id && $user->id !== $studentId) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak melihat informasi ini',
            ], 403);
        }

        $isEnrolled = $class->hasStudent($studentId);

        return response()->json([
            'success' => true,
            'data' => [
                'class_id' => $classId,
                'student_id' => $studentId,
                'is_enrolled' => $isEnrolled,
            ],
        ]);
    }
}

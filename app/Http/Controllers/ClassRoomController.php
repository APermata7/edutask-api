<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClassRoomController extends Controller
{
    /**
     * Get all classes (lecturer gets their own, student gets enrolled ones)
     */
    public function index(Request $request)
    {
        $user = JWTAuth::user();

        if ($user->role === 'lecturer') {
            $classes = ClassRoom::where('lecturer_id', $user->id)
                ->with('enrollments', 'lecturer')
                ->get()
                ->map(function ($class) {
                    $class->enrolled_count = $class->getEnrolledCountAttribute();
                    return $class;
                });

            return response()->json([
                'success' => true,
                'message' => 'Daftar kelas Anda',
                'data' => $classes,
            ]);
        } elseif ($user->role === 'student') {
            $classes = ClassRoom::whereHas('enrollments', function ($query) use ($user) {
                $query->where('student_id', $user->id)
                    ->where('status', 'active');
            })
            ->with('lecturer', 'enrollments')
            ->get()
            ->map(function ($class) {
                $class->enrolled_count = $class->getEnrolledCountAttribute();
                return $class;
            });

            return response()->json([
                'success' => true,
                'message' => 'Daftar kelas Anda',
                'data' => $classes,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Role tidak valid',
        ], 403);
    }

    /**
     * Get a specific class
     */
    public function show($id)
    {
        $user = JWTAuth::user();
        $class = ClassRoom::find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Kelas tidak ditemukan',
            ], 404);
        }

        // Check if user is lecturer or enrolled student
        if ($class->lecturer_id !== $user->id && !$class->hasStudent($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke kelas ini',
            ], 403);
        }

        $class->load('lecturer', 'enrollments', 'students');
        $class->enrolled_count = $class->getEnrolledCountAttribute();

        return response()->json([
            'success' => true,
            'message' => 'Detail kelas',
            'data' => $class,
        ]);
    }

    /**
     * Create a new class (lecturer only)
     */
    public function store(Request $request)
    {
        $user = JWTAuth::user();

        if ($user->role !== 'lecturer') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya dosen yang dapat membuat kelas',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'max_students' => 'nullable|integer|min:1',
            'status' => 'nullable|in:active,inactive',
        ]);

        $validated['lecturer_id'] = $user->id;

        $class = ClassRoom::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kelas berhasil dibuat',
            'data' => $class,
        ], 201);
    }

    /**
     * Update a class (lecturer only, must be owner)
     */
    public function update(Request $request, $id)
    {
        $user = JWTAuth::user();
        $class = ClassRoom::find($id);

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
                'message' => 'Anda tidak berhak mengubah kelas ini',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'max_students' => 'nullable|integer|min:1',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $class->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kelas berhasil diperbarui',
            'data' => $class,
        ]);
    }

    /**
     * Delete a class (lecturer only, must be owner)
     */
    public function destroy($id)
    {
        $user = JWTAuth::user();
        $class = ClassRoom::find($id);

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
                'message' => 'Anda tidak berhak menghapus kelas ini',
            ], 403);
        }

        $class->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kelas berhasil dihapus',
        ]);
    }

    /**
     * Join a class using invite code (student only)
     */
    public function joinByCode(Request $request)
    {
        $user = JWTAuth::user();

        if ($user->role !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya siswa yang dapat bergabung dengan kelas',
            ], 403);
        }

        $validated = $request->validate([
            'invite_code' => 'required|string|size:8',
        ]);

        $class = ClassRoom::where('invite_code', $validated['invite_code'])
            ->where('status', 'active')
            ->first();

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Kode undangan tidak valid atau kelas sudah tidak aktif',
            ], 404);
        }

        // Check if already enrolled
        if ($class->hasStudent($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah terdaftar di kelas ini',
            ], 409);
        }

        // Check if class is full
        if ($class->isFull()) {
            return response()->json([
                'success' => false,
                'message' => 'Kelas sudah penuh, tidak dapat bergabung',
            ], 409);
        }

        // Create enrollment
        $enrollment = Enrollment::create([
            'class_id' => $class->id,
            'student_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil bergabung dengan kelas',
            'data' => [
                'class' => $class,
                'enrollment' => $enrollment,
            ],
        ], 201);
    }

    /**
     * Get invite code for a class (lecturer only, must be owner)
     */
    public function getInviteCode($id)
    {
        $user = JWTAuth::user();
        $class = ClassRoom::find($id);

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
                'message' => 'Anda tidak berhak melihat kode kelas ini',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'class_id' => $class->id,
                'invite_code' => $class->invite_code,
                'class_name' => $class->name,
            ],
        ]);
    }
}

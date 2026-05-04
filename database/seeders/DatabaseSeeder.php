<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\Feedback;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use App\Modules\Assignments\Models\Assignment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $lecturer = User::updateOrCreate(
            ['email' => 'lecturer@example.com'],
            [
                'name' => 'Demo Lecturer',
                'password' => Hash::make('password'),
                'role' => 'lecturer',
            ]
        );

        $student = User::updateOrCreate(
            ['email' => 'student@example.com'],
            [
                'name' => 'Demo Student',
                'password' => Hash::make('password'),
                'role' => 'student',
            ]
        );

        $classroom = ClassRoom::firstOrNew(['name' => 'Pemrograman Web Dasar']);
        $classroom->fill([
            'description' => 'Kelas dummy untuk mencoba fitur task, submission, grade, dan feedback.',
            'lecturer_id' => $lecturer->id,
            'max_students' => 30,
            'status' => 'active',
        ]);

        if (!$classroom->exists && empty($classroom->invite_code)) {
            $classroom->invite_code = Str::upper(Str::random(8));
        }

        $classroom->save();

        Enrollment::updateOrCreate(
            [
                'class_id' => $classroom->id,
                'student_id' => $student->id,
            ],
            [
                'status' => 'active',
            ]
        );

        $assignment = Assignment::updateOrCreate(
            [
                'class_id' => $classroom->id,
                'title' => 'Membuat Halaman Profil',
            ],
            [
                'lecturer_id' => $lecturer->id,
                'description' => 'Buat halaman profil sederhana menggunakan HTML dan CSS.',
                'instructions' => 'Kumpulkan link repository atau file hasil pekerjaan.',
                'due_at' => now()->addDays(7),
                'published_at' => now(),
                'status' => Assignment::STATUS_PUBLISHED,
                'attachment_path' => null,
            ]
        );

        $submission = Submission::updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
            ],
            [
                'content' => 'Repository: https://example.com/demo-profile',
                'submitted_at' => now(),
                'status' => 'submitted',
                'grade' => 88,
                'feedback' => 'Struktur halaman sudah rapi, perlu sedikit perbaikan responsive layout.',
            ]
        );

        Grade::updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'lecturer_id' => $lecturer->id,
                'name' => $student->name,
                'score' => 88,
                'feedback' => 'Struktur halaman sudah rapi, perlu sedikit perbaikan responsive layout.',
            ]
        );

        Feedback::updateOrCreate(
            [
                'submission_id' => $submission->id,
                'message' => 'Struktur halaman sudah rapi, perlu sedikit perbaikan responsive layout.',
            ],
            [
                'user_id' => $lecturer->id,
                'rating' => 5,
            ]
        );

        Feedback::updateOrCreate(
            [
                'message' => 'Fitur feedback sudah berjalan, tetapi perlu filter dan pagination.',
            ],
            [
                'user_id' => $student->id,
                'rating' => 4,
            ]
        );
    }
}

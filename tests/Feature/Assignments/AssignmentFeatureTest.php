<?php

namespace Tests\Feature\Assignments;

use App\Models\User;
use App\Modules\Assignments\Models\Assignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssignmentFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_lecturer_can_create_assignment(): void
    {
        $lecturer = User::factory()->create([
            'role' => 'lecturer',
        ]);

        $classId = DB::table('classes')->insertGetId([
            'lecturer_id' => $lecturer->id,
            'code' => 'IF-01',
            'name' => 'Pemrograman Web',
            'description' => null,
            'semester' => '2025/2026',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($lecturer, 'api')->postJson('/api/assignments', [
            'class_id' => $classId,
            'title' => 'Tugas 1',
            'description' => 'Buat REST API sederhana',
            'instructions' => 'Gunakan Laravel',
            'due_at' => now()->addWeek()->toDateTimeString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Tugas 1')
            ->assertJsonPath('data.class_id', $classId)
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_student_cannot_create_assignment(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $response = $this->actingAs($student, 'api')->postJson('/api/assignments', [
            'class_id' => 1,
            'title' => 'Tugas 1',
            'description' => 'Tidak boleh',
            'instructions' => 'Tidak boleh',
            'due_at' => now()->addWeek()->toDateTimeString(),
        ]);

        $response->assertForbidden();
    }

    public function test_lecturer_can_publish_own_assignment(): void
    {
        $lecturer = User::factory()->create([
            'role' => 'lecturer',
        ]);

        $assignment = Assignment::create([
            'class_id' => 1,
            'lecturer_id' => $lecturer->id,
            'title' => 'Tugas Draft',
            'description' => null,
            'instructions' => null,
            'due_at' => now()->addWeek(),
            'status' => Assignment::STATUS_DRAFT,
        ]);

        $response = $this->actingAs($lecturer, 'api')
            ->patchJson("/api/assignments/{$assignment->id}/publish");

        $response->assertOk()
            ->assertJsonPath('data.status', 'published');
    }
}
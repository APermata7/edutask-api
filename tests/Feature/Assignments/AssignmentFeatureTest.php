<?php

namespace Tests\Feature\Assignments;

use App\Models\ClassRoom;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use App\Modules\Assignments\Models\Assignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function authHeadersFor(User $user): array
    {
        $token = JWTAuth::fromUser($user);

        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }

    public function test_lecturer_can_create_assignment(): void
    {
        $lecturer = User::factory()->create([
            'role' => 'lecturer',
        ]);

        $class = ClassRoom::create([
            'name' => 'Pemrograman Web',
            'description' => null,
            'lecturer_id' => $lecturer->id,
            'max_students' => 30,
            'status' => 'active',
        ]);

        $response = $this->withHeaders($this->authHeadersFor($lecturer))
        ->postJson('/api/assignments', 
        [
            'class_id' => $class->id,
            'title' => 'Tugas 1',
            'description' => 'Buat REST API sederhana',
            'instructions' => 'Gunakan Laravel',
            'due_at' => now()->addWeek()->toDateTimeString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Tugas 1')
            ->assertJsonPath('data.class_id', $class->id)
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_student_cannot_create_assignment(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $response = $this->withHeaders($this->authHeadersFor($student))
        ->postJson('/api/assignments', 
        [
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

        $class = ClassRoom::create([
            'name' => 'Pemrograman Web',
            'description' => null,
            'lecturer_id' => $lecturer->id,
            'max_students' => 30,
            'status' => 'active',
        ]);

        $assignment = Assignment::create([
            'class_id' => $class->id,
            'lecturer_id' => $lecturer->id,
            'title' => 'Tugas Draft',
            'description' => null,
            'instructions' => null,
            'due_at' => now()->addWeek(),
            'status' => Assignment::STATUS_DRAFT,
        ]);

        $response = $this->withHeaders($this->authHeadersFor($lecturer))
        ->patchJson("/api/assignments/{$assignment->id}/publish");

        $response->assertOk()
            ->assertJsonPath('data.status', 'published');
    }
}

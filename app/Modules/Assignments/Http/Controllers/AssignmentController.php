<?php

namespace App\Modules\Assignments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assignments\Http\Requests\StoreAssignmentRequest;
use App\Modules\Assignments\Http\Requests\UpdateAssignmentRequest;
use App\Modules\Assignments\Http\Resources\AssignmentResource;
use App\Modules\Assignments\Models\Assignment;
use App\Modules\Assignments\Services\AssignmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    private function success(string $message, mixed $data = null, int $code = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message,
        ];

        if (!is_null($data)) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $code);
    }

    public function index(Request $request, AssignmentService $service): JsonResponse
    {
        $this->authorize('viewAny', Assignment::class);

        $assignments = $service->paginateForUser(
            $request->user(),
            $request->only(['class_id', 'status', 'search']),
            (int) $request->integer('per_page', 15)
        );

        return $this->success(
            'Daftar assignment berhasil diambil',
            AssignmentResource::collection($assignments)->response()->getData(true)
        );
    }

    public function store(StoreAssignmentRequest $request, AssignmentService $service): JsonResponse
    {
        $assignment = $service->create($request->validated(), $request->user());

        return $this->success(
            'Assignment berhasil dibuat',
            (new AssignmentResource($assignment->loadMissing(['lecturer', 'classroom'])))->resolve(),
            201
        );
    }

    public function show(Assignment $assignment): JsonResponse
    {
        $this->authorize('view', $assignment);

        return $this->success(
            'Detail assignment berhasil diambil',
            (new AssignmentResource($assignment->loadMissing(['lecturer', 'classroom'])))->resolve()
        );
    }

    public function update(
        UpdateAssignmentRequest $request,
        Assignment $assignment,
        AssignmentService $service
    ): JsonResponse {
        $this->authorize('update', $assignment);

        $updated = $service->update($assignment, $request->validated());

        return $this->success(
            'Assignment berhasil diperbarui',
            (new AssignmentResource($updated->loadMissing(['lecturer', 'classroom'])))->resolve()
        );
    }

    public function destroy(Assignment $assignment, AssignmentService $service): JsonResponse
    {
        $this->authorize('delete', $assignment);

        $service->delete($assignment);

        return $this->success('Assignment berhasil dihapus');
    }

    public function publish(Assignment $assignment, AssignmentService $service): JsonResponse
    {
        $this->authorize('publish', $assignment);

        $published = $service->publish($assignment);

        return $this->success(
            'Assignment berhasil dipublish',
            (new AssignmentResource($published->loadMissing(['lecturer', 'classroom'])))->resolve()
        );
    }

    public function byClass(Request $request, int $classId, AssignmentService $service): JsonResponse
    {
        $assignments = $service->paginateByClassForUser(
            $classId,
            $request->user(),
            (int) $request->integer('per_page', 15)
        );

        return $this->success(
            'Daftar assignment per kelas berhasil diambil',
            AssignmentResource::collection($assignments)->response()->getData(true)
        );
    }
}
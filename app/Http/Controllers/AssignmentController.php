<?php

namespace App\Modules\Assignments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assignments\Http\Requests\StoreAssignmentRequest;
use App\Modules\Assignments\Http\Requests\UpdateAssignmentRequest;
use App\Modules\Assignments\Http\Resources\AssignmentResource;
use App\Modules\Assignments\Models\Assignment;
use App\Modules\Assignments\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index(Request $request, AssignmentService $service)
    {
        $this->authorize('viewAny', Assignment::class);

        $assignments = $service->paginateForUser(
            $request->user(),
            $request->only(['class_id', 'status', 'search']),
            (int) $request->integer('per_page', 15)
        );

        return AssignmentResource::collection($assignments);
    }

    public function store(StoreAssignmentRequest $request, AssignmentService $service): JsonResponse
    {
        $assignment = $service->create($request->validated(), $request->user());

        return (new AssignmentResource($assignment))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Assignment $assignment): AssignmentResource
    {
        $this->authorize('view', $assignment);

        return new AssignmentResource($assignment->loadMissing('lecturer'));
    }

    public function update(UpdateAssignmentRequest $request, Assignment $assignment, AssignmentService $service): AssignmentResource
    {
        $this->authorize('update', $assignment);

        $assignment = $service->update($assignment, $request->validated());

        return new AssignmentResource($assignment);
    }

    public function publish(Assignment $assignment, AssignmentService $service): AssignmentResource
    {
        $this->authorize('publish', $assignment);

        $assignment = $service->publish($assignment);

        return new AssignmentResource($assignment);
    }

    public function destroy(Assignment $assignment, AssignmentService $service): JsonResponse
    {
        $this->authorize('delete', $assignment);

        $service->delete($assignment);

        return response()->json([
            'message' => 'Assignment deleted successfully',
        ]);
    }

    public function byClass(Request $request, int $classId, AssignmentService $service)
    {
        $assignments = $service->paginateByClassForUser(
            $classId,
            $request->user(),
            (int) $request->integer('per_page', 15)
        );

        return AssignmentResource::collection($assignments);
    }
}
<?php

namespace App\Http\Controllers;

use App\Services\FeedbackService;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    protected FeedbackService $feedbackService;

    public function __construct(FeedbackService $feedbackService)
    {
        $this->feedbackService = $feedbackService;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'submission_id' => 'nullable|exists:submissions,id',
            'message' => 'required|string',
            'rating' => 'required|integer|min:1|max:5'
        ]);

        if (isset($validated['submission_id']) && !$this->canAccessSubmission((int) $validated['submission_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak punya akses ke submission ini'
            ], 403);
        }

        $validated['user_id'] = auth()->id();

        $feedback = $this->feedbackService->createFeedback($validated);

        return response()->json([
            'message' => 'Feedback created',
            'data' => $feedback->load(['submission.assignment', 'submission.student', 'user'])
        ], 201);
    }

    public function index(Request $request)
    {
        $query = \App\Models\Feedback::with(['submission.assignment.classroom', 'submission.student', 'user']);
        $user = $request->user();

        if ($request->has('submission_id')) {
            $query->where('submission_id', $request->submission_id);
        }

        if ($user->role === 'student') {
            $query->where(function ($feedbackQuery) use ($user) {
                $feedbackQuery
                    ->where('user_id', $user->id)
                    ->orWhereHas('submission', function ($submissionQuery) use ($user) {
                        $submissionQuery->where('student_id', $user->id);
                    });
            });
        }

        if ($user->role === 'lecturer') {
            $query->where(function ($feedbackQuery) use ($user) {
                $feedbackQuery
                    ->where('user_id', $user->id)
                    ->orWhereHas('submission.assignment.classroom', function ($classQuery) use ($user) {
                        $classQuery->where('lecturer_id', $user->id);
                    });
            });
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar feedback',
            'data' => $query->latest()->get(),
        ]);
    }

    public function show($id)
    {
        $feedback = $this->feedbackService->find($id);

        if (!$feedback) {
            return response()->json([
                'message' => 'Feedback not found'
            ], 404);
        }

        if (!$this->canAccessFeedback($feedback)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak punya akses ke feedback ini'
            ], 403);
        }

        return response()->json($feedback->load(['submission.assignment', 'submission.student', 'user']));
    }

    public function update(Request $request, $id)
    {
        $existingFeedback = $this->feedbackService->find($id);

        if (!$existingFeedback) {
            return response()->json([
                'message' => 'Feedback not found'
            ], 404);
        }

        if (!$this->canAccessFeedback($existingFeedback)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak punya akses ke feedback ini'
            ], 403);
        }

        $validated = $request->validate([
            'submission_id' => 'sometimes|nullable|exists:submissions,id',
            'message' => 'sometimes|required|string',
            'rating' => 'sometimes|required|integer|min:1|max:5'
        ]);

        if (isset($validated['submission_id']) && !$this->canAccessSubmission((int) $validated['submission_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak punya akses ke submission ini'
            ], 403);
        }

        $feedback = $this->feedbackService->updateFeedback($id, $validated);

        if (!$feedback) {
            return response()->json([
                'message' => 'Feedback not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Feedback updated',
            'data' => $feedback->load(['submission.assignment', 'submission.student', 'user'])
        ]);
    }

    public function destroy($id)
    {
        $feedback = $this->feedbackService->find($id);

        if (!$feedback) {
            return response()->json([
                'message' => 'Feedback not found'
            ], 404);
        }

        if (!$this->canAccessFeedback($feedback)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak punya akses ke feedback ini'
            ], 403);
        }

        if (!$this->feedbackService->deleteFeedback($id)) {
            return response()->json([
                'message' => 'Feedback not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Feedback deleted'
        ]);
    }

    private function canAccessSubmission(int $submissionId): bool
    {
        $submission = \App\Models\Submission::with('assignment.classroom')->find($submissionId);
        $user = auth()->user();

        if (!$submission || !$user) {
            return false;
        }

        if ($user->role === 'student') {
            return (int) $submission->student_id === (int) $user->id;
        }

        return $user->role === 'lecturer'
            && (int) $submission->assignment->classroom->lecturer_id === (int) $user->id;
    }

    private function canAccessFeedback(\App\Models\Feedback $feedback): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if ($feedback->submission_id) {
            return $this->canAccessSubmission((int) $feedback->submission_id);
        }

        return !$feedback->user_id || (int) $feedback->user_id === (int) $user->id;
    }
}

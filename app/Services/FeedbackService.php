<?php

namespace App\Services;

use App\Models\Feedback;

class FeedbackService
{
    public function createFeedback(array $data): Feedback
    {
        return Feedback::create($data);
    }

    public function getAll()
    {
        return Feedback::with(['submission.assignment', 'submission.student', 'user'])
            ->latest()
            ->get();
    }

    public function find(int $id): ?Feedback
    {
        return Feedback::find($id);
    }

    public function updateFeedback(int $id, array $data): ?Feedback
    {
        $feedback = $this->find($id);

        if (!$feedback) {
            return null;
        }

        $feedback->update($data);

        return $feedback;
    }

    public function deleteFeedback(int $id): bool
    {
        $feedback = $this->find($id);

        if (!$feedback) {
            return false;
        }

        return (bool) $feedback->delete();
    }
}

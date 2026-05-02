<?php

namespace App\Modules\Assignments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isLecturer() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'instructions' => ['sometimes', 'nullable', 'string'],
            'due_at' => ['sometimes', 'required', 'date', 'after:now'],
            'attachment_path' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
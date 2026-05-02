<?php

namespace App\Modules\Assignments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isLecturer() ?? false;
    }

    public function rules(): array
    {
        return [
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'due_at' => ['required', 'date', 'after:now'],
            'attachment_path' => ['nullable', 'string', 'max:255'],
        ];
    }
}
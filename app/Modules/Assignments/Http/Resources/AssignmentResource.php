<?php

namespace App\Modules\Assignments\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'class_id' => $this->class_id,
            'lecturer_id' => $this->lecturer_id,
            'title' => $this->title,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'due_at' => $this->due_at?->toISOString(),
            'published_at' => $this->published_at?->toISOString(),
            'status' => $this->status,
            'attachment_path' => $this->attachment_path,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'lecturer' => $this->whenLoaded('lecturer', function () {
                return [
                    'id' => $this->lecturer->id,
                    'name' => $this->lecturer->name,
                    'email' => $this->lecturer->email,
                ];
            }),
            'classroom' => $this->whenLoaded('classroom', function () {
                return [
                    'id' => $this->classroom->id,
                    'name' => $this->classroom->name,
                    'lecturer_id' => $this->classroom->lecturer_id,
                ];
            }),
        ];
    }
}
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'assigned_to' => $this->assigned_to,
            'assignee_name' => $this->whenLoaded('assignee', fn () => $this->assignee->name),
            'assigned_by' => $this->assigned_by,
            'assigner_name' => $this->whenLoaded('assigner', fn () => $this->assigner->name),
            'status' => $this->status,
            'related_task_id' => $this->related_task_id,
            'due_date' => $this->due_date?->toDateString(),
            'steps' => TaskStepResource::collection($this->whenLoaded('steps')),
            'created_at' => $this->created_at,
        ];
    }
}

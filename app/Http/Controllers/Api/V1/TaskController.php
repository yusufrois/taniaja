<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFollowUpTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\StoreTaskStepRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\TaskStep;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', Task::class);

        $query = Task::with(['assignee', 'assigner'])->orderByDesc('id');

        if (! auth()->user()->hasPermission('task.view')) {
            $userId = auth()->id();
            $query->where(fn ($q) => $q->where('assigned_to', $userId)->orWhere('assigned_by', $userId));
        }

        return TaskResource::collection($query->paginate(20));
    }

    public function store(StoreTaskRequest $request, NotificationService $notifications)
    {
        $this->authorize('create', Task::class);

        $task = DB::transaction(function () use ($request) {
            $task = Task::create($request->safe()->except('steps') + [
                'company_id' => $request->user()->company_id,
                'assigned_by' => $request->user()->id,
            ]);

            foreach ($request->input('steps', []) as $i => $description) {
                TaskStep::create([
                    'company_id' => $request->user()->company_id,
                    'task_id' => $task->id,
                    'description' => $description,
                    'order' => $i,
                ]);
            }

            return $task;
        });

        $notifications->notify(
            User::find($task->assigned_to),
            'task_assigned',
            'Tugas baru: '.$task->title,
            $task->description ?? '',
            ['task_id' => $task->id]
        );

        $this->logAudit('create', $task, null, $task->toArray());

        return new TaskResource($task->load(['assignee', 'assigner', 'steps']));
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);

        return new TaskResource($task->load(['assignee', 'assigner', 'steps', 'followUps']));
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $old = $task->toArray();
        $task->update($request->validated());
        $this->logAudit('update', $task, $old, $task->toArray());

        return new TaskResource($task->load(['assignee', 'assigner', 'steps']));
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();
        $this->logAudit('delete', $task);

        return response()->json(['message' => 'Tugas berhasil dihapus.']);
    }

    /**
     * The assignee's main day-to-day action — progressing their own
     * task. Deliberately checked via the LIGHTER 'view' bar (see
     * UpdateTaskStatusRequest/TaskPolicy), not full 'update'.
     */
    public function updateStatus(UpdateTaskStatusRequest $request, Task $task)
    {
        $old = $task->only('status');
        $task->update(['status' => $request->validated('status')]);
        $this->logAudit('task.status_change', $task, $old, ['status' => $task->status]);

        return new TaskResource($task->load(['assignee', 'assigner', 'steps']));
    }

    public function addStep(StoreTaskStepRequest $request, Task $task)
    {
        $maxOrder = $task->steps()->max('order') ?? -1;

        $step = TaskStep::create([
            'company_id' => $task->company_id,
            'task_id' => $task->id,
            'description' => $request->validated('description'),
            'order' => $maxOrder + 1,
        ]);

        $this->logAudit('create', $step, null, $step->toArray());

        return new TaskResource($task->fresh(['assignee', 'assigner', 'steps']));
    }

    /**
     * Checking a box — the assignee's core interaction, same 'view'
     * bar as updateStatus().
     */
    public function toggleStep(Task $task, TaskStep $step)
    {
        abort_if($step->task_id !== $task->id, 404);
        $this->authorize('view', $task);

        $step->update([
            'is_done' => ! $step->is_done,
            'completed_at' => ! $step->is_done ? now() : null,
        ]);

        return new TaskResource($task->fresh(['assignee', 'assigner', 'steps']));
    }

    /**
     * "Tugas susulan" — a NEW Task, related_task_id pointing back at
     * the original. Notifies the assignee same as a normal store().
     */
    public function followUp(StoreFollowUpTaskRequest $request, Task $task, NotificationService $notifications)
    {
        $this->authorize('create', Task::class);

        $followUp = Task::create($request->validated() + [
            'company_id' => $request->user()->company_id,
            'assigned_by' => $request->user()->id,
            'related_task_id' => $task->id,
        ]);

        $notifications->notify(
            User::find($followUp->assigned_to),
            'task_assigned',
            'Tugas susulan: '.$followUp->title,
            $followUp->description ?? '',
            ['task_id' => $followUp->id, 'related_task_id' => $task->id]
        );

        $this->logAudit('create', $followUp, null, $followUp->toArray());

        return new TaskResource($followUp->load(['assignee', 'assigner']));
    }
}

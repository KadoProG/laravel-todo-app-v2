<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskFilterRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    /** タスク一覧取得 */
    public function index(TaskFilterRequest $request)
    {
        $filters = $request->validatedFilters();
        $query = Task::with(['createdUser', 'assignedUsers'])->filter($filters);
        Log::info($query->toSql(), $query->getBindings());

        if ($request->filled('sort_by')) {
            $query->orderBy($request->input('sort_by'), $request->input('sort_order', 'desc'));
        }

        $tasks = $query->get();

        return response()->json(['tasks' => TaskResource::collection($tasks)]);
    }

    /** タスク単体取得 */
    public function show(Task $task): JsonResource
    {
        $task->load(['createdUser', 'assignedUsers']);

        return new TaskResource($task);
    }

    /** 新規タスク作成 */
    public function store(StoreTaskRequest $request): JsonResource
    {
        $validated = $request->validated();
        $user = $request->user();
        $validated['created_user_id'] = $user->id;
        $assignedUserIds = $validated['assigned_user_ids'] ?? null;
        unset($validated['assigned_user_ids']);
        $task = DB::transaction(function () use ($validated, $assignedUserIds) {
            $task = Task::create($validated);
            if (! is_null($assignedUserIds)) {
                $task->assignedUsers()->sync($assignedUserIds);
            }
            return $task;
        });
        return new TaskResource($task);
    }

    /** タスク更新 */
    public function update(UpdateTaskRequest $request, Task $task): JsonResource
    {
        $validated = $request->validated();
        $assignedUserIds = $validated['assigned_user_ids'] ?? null;
        unset($validated['assigned_user_ids']);
        DB::transaction(function () use ($task, $validated, $assignedUserIds) {
            $task->update($validated);
            if (! is_null($assignedUserIds)) {
                $task->assignedUsers()->sync($assignedUserIds);
            }
        });
        return new TaskResource($task);
    }

    /** タスク削除 */
    public function destroy(Task $task)
    {
        $task->delete();

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskController extends Controller
{
    /** タスク一覧取得 */
    public function index()
    {
        $tasks = Task::with(['createdUser', 'assignedUsers'])->get();

        return response()->json(['tasks' => TaskResource::collection($tasks)]);
    }

    /** タスク単体取得 */
    public function show(Task $task): JsonResource
    {
        $task->load(['createdUser', 'assignedUsers']);

        return new TaskResource($task);
    }

    /** 新規タスク作成 */
    public function store(Request $request): JsonResource
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'is_public' => 'required|boolean',
            'description' => 'nullable|string',
            'expired_at' => 'nullable|date',
            'assigned_user_ids' => 'nullable|array',
            'assigned_user_ids.*' => 'required|integer|exists:users,id',
        ]);

        $user = $request->user();

        $validated['created_user_id'] = $user->id;

        $task = Task::create($validated);

        return new TaskResource($task);
    }

    /** タスク更新 */
    public function update(Request $request, Task $task): JsonResource
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'is_public' => 'sometimes|boolean',
            'expired_at' => 'nullable|date',
            'description' => 'nullable|string',
            'is_done' => 'sometimes|boolean',
            'assigned_user_ids' => 'nullable|array',
            'assigned_user_ids.*' => 'required|integer|exists:users,id',
        ]);

        $task->update($validated);

        return new TaskResource($task); // 更新されたタスクをTaskResourceで返す
    }

    /** タスク削除 */
    public function destroy(Task $task)
    {
        $task->delete();

        return response()->noContent();
    }
}

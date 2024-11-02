<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskController extends Controller
{
    // GET /tasks - 全タスクの取得
    public function index(): JsonResource
    {
        $tasks = Task::all();

        return TaskResource::collection($tasks);
    }

    // GET /tasks/{task} - 特定タスクの取得
    public function show(Task $task): JsonResource
    {
        return new TaskResource($task);
    }

    // POST /tasks - 新しいタスクの作成
    public function store(Request $request): JsonResource
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $task = Task::create($validated);

        return new TaskResource($task);
    }

    // PUT /tasks/{task} - タスクの更新
    public function update(Request $request, Task $task): JsonResource
    {
        $validated = $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'isDone' => 'sometimes|boolean',
        ]);

        $validated['is_done'] = $validated['isDone'] ?? $task->is_done;

        $task->update($validated);

        return new TaskResource($task); // 更新されたタスクをTaskResourceで返す
    }

    // DELETE /tasks/{task} - タスクの削除
    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json(null, 204);
    }
}

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
    /** タスク一覧取得 */
    public function index(): JsonResource
    {
        $tasks = Task::with('children')->whereNull('parent_id')->get();

        return TaskResource::collection($tasks);
    }

    /** タスク単体取得 */
    public function show(Task $task): JsonResource
    {
        $task->load('children');

        return new TaskResource($task);
    }

    /** 新規タスク作成 */
    public function store(Request $request): JsonResource
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parentId' => 'sometimes|integer|exists:tasks,id',
        ]);

        $validated['parent_id'] = $validated['parentId'] ?? null;

        $task = Task::create($validated);

        return new TaskResource($task);
    }

    /** タスク更新 */
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

    /** タスク削除 */
    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json(null, 204);
    }
}

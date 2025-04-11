<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskActionResource;
use App\Models\Task;
use App\Models\TaskAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskActionController extends Controller
{
    /** タスクに対応するアクション一覧 */
    public function index(Request $request, Task $task)
    {
        $actions = $task->actions()->get();

        return response()->json(['actions' => TaskActionResource::collection($actions)]);
    }

    /** タスクに対応するアクション作成 */
    public function store(Request $request, Task $task)
    {
        // TODO パーミッションを追加する
        $validated = $request->validate([
            'name' => 'required|string',
            'is_done' => 'sometimes|boolean',
        ]);

        $action = $task->actions()->create($validated);

        return new TaskActionResource($action);
    }

    /** タスクに対応するアクション更新 */
    public function update(Request $request, Task $task, TaskAction $action)
    {
        // TODO パーミッションを追加する
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'is_done' => 'sometimes|boolean',
        ]);

        $action->update($validated);

        return new TaskActionResource($action);
    }

    /** タスクに対応するアクション削除 */
    public function destroy(Request $request, Task $task, TaskAction $action): JsonResponse
    {
        $action->delete();
        return response()->json(null, 204);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskActionRequest;
use App\Http\Requests\UpdateTaskActionRequest;
use App\Http\Resources\TaskActionResource;
use App\Models\Task;
use App\Models\TaskAction;
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
    public function store(StoreTaskActionRequest $request, Task $task)
    {
        $validated = $request->validated();
        $action = $task->actions()->create($validated);

        return new TaskActionResource($action);
    }

    /** タスクに対応するアクション更新 */
    public function update(UpdateTaskActionRequest $request, Task $task, TaskAction $action)
    {
        $validated = $request->validated();
        $action->update($validated);

        return new TaskActionResource($action);
    }

    /** タスクに対応するアクション削除 */
    public function destroy(Request $request, Task $task, TaskAction $action)
    {
        $action->delete();

        return response()->noContent();
    }
}

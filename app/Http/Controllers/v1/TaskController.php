<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskFilterRequest;
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

        $assignedUserIds = $validated['assigned_user_ids'] ?? null;
        unset($validated['assigned_user_ids']);

        // DBトランザクションで安全にタスクとリレーションを保存
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

        // assigned_user_idsはリレーション用なので切り離し
        $assignedUserIds = $validated['assigned_user_ids'] ?? null;
        unset($validated['assigned_user_ids']);

        // DBトランザクションで安全に更新
        DB::transaction(function () use ($task, $validated, $assignedUserIds) {
            $task->update($validated);

            // assigned_user_idsが指定されていればリレーションを更新
            if (! is_null($assignedUserIds)) {
                $task->assignedUsers()->sync($assignedUserIds);
            }
        });

        return new TaskResource($task); // 更新されたタスクをTaskResourceで返す
    }

    /** タスク削除 */
    public function destroy(Task $task)
    {
        $task->delete();

        return response()->noContent();
    }
}

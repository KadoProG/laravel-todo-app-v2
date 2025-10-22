<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskFilterRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;

class UserMeTaskController extends Controller
{
    /** 自身のタスク一覧取得 */
    public function index(TaskFilterRequest $request)
    {
        $user = $request->user();
        $filters = $request->validatedFilters();

        $query = Task::with(['createdUser', 'assignedUsers'])->filter($filters)
            ->where('created_user_id', $user->id)
            ->orWhereHas('assignedUsers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

        if ($request->filled('sort_by')) {
            $query->orderBy($request->input('sort_by'), $request->input('sort_order', 'desc'));
        }

        $tasks = $query->get();

        return response()->json(['tasks' => TaskResource::collection($tasks)]);
    }
}

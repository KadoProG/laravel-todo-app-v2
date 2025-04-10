<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;

class UserMeTaskController extends Controller
{
    /** 自身のタスク一覧取得 */
    public function index(Request $request)
    {
        $user = $request->user();
        $tasksQuery = Task::with(['createdUser', 'assignedUsers'])
            ->where('created_user_id', $user->id)
            ->orWhereHas('assignedUsers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

        $tasks = $tasksQuery->get();

        return response()->json(['tasks' => TaskResource::collection($tasks)]);
    }
}

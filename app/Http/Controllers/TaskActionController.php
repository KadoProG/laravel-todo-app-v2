<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskActionResource;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskActionController extends Controller
{
    public function index(Request $request, Task $task)
    {
        $actions = $task->actions()->get();

        return response()->json(['actions' => TaskActionResource::collection($actions)]);
    }
}

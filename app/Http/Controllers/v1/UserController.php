<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /** ユーザ一覧取得 */
    public function index()
    {
        $users = User::all();

        return response()->json(['users' => UserResource::collection($users)]);
    }

    /** 自身のユーザ取得 */
    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->noContent();
    }
}

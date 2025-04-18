<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
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

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        $user->update($validated);

        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->noContent();
    }
}

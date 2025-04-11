<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /** ユーザー登録 */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(compact('user', 'token'), 201);
    }

    /** ログイン */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json(compact('token'));
    }

    /** ログアウト */
    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /** ログインしているユーザー情報の取得 */
    public function me()
    {
        return response()->json(Auth::guard('api')->user());
    }

    /** トークンのリフレッシュ */
    public function refresh()
    {
        try {
            /** トークンをリフレッシュ */
            $token = JWTAuth::getToken();

            if (! $token) {
                return response()->json(['error' => 'Token not provided'], 400);
            }

            $newToken = JWTAuth::refresh($token);

            return response()->json(['token' => $newToken]);
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Could not refresh token',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }
}

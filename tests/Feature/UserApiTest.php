<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserApiTest extends TestCase
{
    use RefreshDatabase; // データベースのリフレッシュ

    public function testAuthenticatedRequest()
    {
        // テストユーザーの作成
        $user = User::factory()->create();

        // JWTトークンを発行
        $token = JWTAuth::fromUser($user);

        // トークンをAuthorizationヘッダーに設定してリクエスト
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->json('GET', '/api/v1/users/me');

        // レスポンスの検証
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => [
                'id',
                'name',
                'email',
            ],
        ]);
    }

    public function testTokenRefresh()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // トークンのリフレッシュ
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post('/api/v1/refresh');

        $newToken = $response->json('token');

        // 新しいトークンで認証されたリクエストをテスト
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$newToken,
        ])->json('GET', '/api/v1/users/me');

        $response->assertStatus(200);
    }
}

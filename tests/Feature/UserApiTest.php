<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserApiTest extends TestCase
{
    use RefreshDatabase; // データベースのリフレッシュ

    public function test_authenticated_request()
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

    public function test_token_refresh()
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

    public function test_icon_is_null_when_not_uploaded()
    {
        $user = User::factory()->create();

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->json('GET', '/api/v1/users/me');

        $response->assertStatus(200);
        $response->assertJsonPath('user.icon_url', null);
    }

    public function test_upload_icon()
    {
        $disk = Storage::fake(config('filesystems.icons'));
        $user = User::factory()->create();

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->json('POST', "/api/v1/users/{$user->id}/icon", [
                'icon' => UploadedFile::fake()->image('icon.png'),
            ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertNotNull($user->icon_path);
        $disk->assertExists($user->icon_path);

        // 更新時刻をクエリに付けてブラウザキャッシュを無効化している
        $response->assertJsonPath(
            'user.icon_url',
            route('users.icon.show', ['user' => $user->id, 'v' => $user->updated_at->timestamp]),
        );
    }

    public function test_icon_url_omits_the_default_port_behind_a_https_proxy()
    {
        Storage::fake(config('filesystems.icons'));
        $user = User::factory()->create();

        // ALB は自身が HTTP で受けるためポート 80 を伝えてくる。nginx がプロトコルだけ
        // https に直すので、ポートまで信頼すると URL に :80 が残り、
        // ブラウザが 80 番へ TLS 接続して ERR_SSL_PROTOCOL_ERROR になる
        $response = $this->withToken(JWTAuth::fromUser($user))
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Port' => '80',
            ])
            ->json('POST', "/api/v1/users/{$user->id}/icon", [
                'icon' => UploadedFile::fake()->image('icon.png'),
            ]);

        $response->assertStatus(200);
        $this->assertStringStartsWith('https://localhost/', $response->json('user.icon_url'));
    }

    public function test_upload_icon_replaces_the_old_file()
    {
        $disk = Storage::fake(config('filesystems.icons'));
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $this->withToken($token)
            ->json('POST', "/api/v1/users/{$user->id}/icon", [
                'icon' => UploadedFile::fake()->image('first.png'),
            ])->assertStatus(200);

        $oldPath = $user->refresh()->icon_path;

        $this->withToken($token)
            ->json('POST', "/api/v1/users/{$user->id}/icon", [
                'icon' => UploadedFile::fake()->image('second.png'),
            ])->assertStatus(200);

        $newPath = $user->refresh()->icon_path;

        $this->assertNotSame($oldPath, $newPath);
        $disk->assertMissing($oldPath);
        $disk->assertExists($newPath);
    }

    public function test_upload_icon_for_another_user_is_forbidden()
    {
        Storage::fake(config('filesystems.icons'));
        $user = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->json('POST', "/api/v1/users/{$other->id}/icon", [
                'icon' => UploadedFile::fake()->image('icon.png'),
            ]);

        $response->assertStatus(403);
        $this->assertNull($other->refresh()->icon_path);
    }

    public function test_upload_icon_rejects_non_image()
    {
        Storage::fake(config('filesystems.icons'));
        $user = User::factory()->create();

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->json('POST', "/api/v1/users/{$user->id}/icon", [
                'icon' => UploadedFile::fake()->create('icon.pdf', 10, 'application/pdf'),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('icon');
    }

    public function test_upload_icon_requires_authentication()
    {
        Storage::fake(config('filesystems.icons'));
        $user = User::factory()->create();

        $response = $this->json('POST', "/api/v1/users/{$user->id}/icon", [
            'icon' => UploadedFile::fake()->image('icon.png'),
        ]);

        $response->assertStatus(401);
    }

    public function test_show_icon()
    {
        Storage::fake(config('filesystems.icons'));
        $user = User::factory()->create();

        $this->withToken(JWTAuth::fromUser($user))
            ->json('POST', "/api/v1/users/{$user->id}/icon", [
                'icon' => UploadedFile::fake()->image('icon.png'),
            ])->assertStatus(200);

        // 認証なしで配信できる
        $response = $this->get("/api/v1/users/{$user->id}/icon");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_show_icon_returns_not_found_when_not_uploaded()
    {
        Storage::fake(config('filesystems.icons'));
        $user = User::factory()->create();

        $this->get("/api/v1/users/{$user->id}/icon")->assertStatus(404);
    }

    public function test_delete_icon()
    {
        $disk = Storage::fake(config('filesystems.icons'));
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $this->withToken($token)
            ->json('POST', "/api/v1/users/{$user->id}/icon", [
                'icon' => UploadedFile::fake()->image('icon.png'),
            ])->assertStatus(200);

        $path = $user->refresh()->icon_path;

        $response = $this->withToken($token)
            ->json('DELETE', "/api/v1/users/{$user->id}/icon");

        $response->assertStatus(200);
        $response->assertJsonPath('user.icon_url', null);
        $disk->assertMissing($path);
        $this->assertNull($user->refresh()->icon_path);
    }

    public function test_delete_icon_of_another_user_is_forbidden()
    {
        $disk = Storage::fake(config('filesystems.icons'));
        $user = User::factory()->create();
        $other = User::factory()->create();

        // 認証済みリクエストを 1 回に絞るため、アップロードは API を通さず直接用意する
        $path = 'user-icons/other.png';
        $disk->put($path, 'dummy');
        $other->icon_path = $path;
        $other->save();

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->json('DELETE', "/api/v1/users/{$other->id}/icon");

        $response->assertStatus(403);
        $disk->assertExists($path);
        $this->assertNotNull($other->refresh()->icon_path);
    }
}

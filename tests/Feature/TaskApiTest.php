<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TaskApiTest extends TestCase
{
    use RefreshDatabase; // データベースのリフレッシュ

    protected $user;

    public function setup(): void
    {
        parent::setUp();

        // テストユーザーの作成
        $user = User::factory()->create();
        $this->user = $user;

        // JWTトークンを発行
        $token = JWTAuth::fromUser($user);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ]);
    }

    public function test_can_get_all_tasks()
    {
        // テストデータの作成
        Task::factory()->count(3)->create(['created_user_id' => $this->user->id, 'is_public' => false]);

        // APIリクエスト
        $response = $this->getJson('/api/v1/tasks');

        // ステータスコードとデータ構造の確認
        $response->assertStatus(200)
            ->assertJsonCount(3, 'tasks'); // tasksキー下
    }

    public function test_can_get_a_single_task()
    {
        // テストデータの作成
        $task = Task::factory()->create(['created_user_id' => $this->user->id, 'is_public' => false]);

        // APIリクエスト
        $response = $this->getJson("/api/v1/tasks/{$task->id}");

        // ステータスコードとデータ内容の確認
        $response->assertStatus(200)
            ->assertJson([
                'task' => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'is_public' => $task->is_public,
                    'is_done' => $task->is_done,
                ],
            ]);
    }

    public function test_can_create_a_task()
    {
        // テスト用データ
        $data = [
            'title' => 'New Task',
            'description' => 'This is a new task.',
            'is_public' => false,
        ];

        // APIリクエスト（POST）
        $response = $this->postJson('/api/v1/tasks', $data);

        // ステータスコードとレスポンスの確認
        $response->assertStatus(201)
            ->assertJson([
                'task' => [
                    'title' => 'New Task',
                    'description' => 'This is a new task.',
                    'is_public' => false,
                    'is_done' => false,
                ],
            ]);

        // データベースに保存されているか確認
        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'description' => 'This is a new task.',
            'is_public' => false,
            'is_done' => false,
            'created_user_id' => $this->user->id,
        ]);
    }

    public function test_can_update_a_task()
    {
        // テストデータの作成
        $task = Task::factory()->create([
            'title' => 'Old Task',
            'is_done' => false,
            'description' => 'This is a new task.',
            'created_user_id' => $this->user->id,
            'is_public' => false,
        ]);

        // 更新用のデータ
        $data = [
            'title' => 'Updated Task',
            'is_done' => true,
            'description' => 'This is a updated task.',
        ];

        // APIリクエスト（PUT）
        $response = $this->putJson("/api/v1/tasks/{$task->id}", $data);

        // ステータスコードとレスポンスの確認
        $response->assertStatus(200)
            ->assertJson([
                'task' => [
                    'title' => 'Updated Task',
                    'is_done' => true,
                    'description' => 'This is a updated task.',
                    'is_public' => false,
                ],
            ]);

        // データベースに反映されているか確認
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Task',
            'is_done' => true,
            'description' => 'This is a updated task.',
            'is_public' => false,
        ]);
    }

    public function test_can_delete_a_task()
    {
        // テストデータの作成
        $task = Task::factory()->create(['created_user_id' => $this->user->id, 'is_public' => false]);

        // APIリクエスト（DELETE）
        $response = $this->deleteJson("/api/v1/tasks/{$task->id}");

        // ステータスコードの確認
        $response->assertStatus(204);

        // データベースから削除されているか確認
        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_validates_title_field_when_creating_a_task()
    {
        // 空のデータでAPIリクエスト
        $response = $this->postJson('/api/v1/tasks', [
            'title' => '', // 空のタイトル
            'description' => 'This is a new task.',
            'isDone' => false,
        ]);

        // バリデーションエラーの確認
        $response->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    public function test_title_must_not_exceed_max_length_when_creating_task()
    {
        $data = [
            'title' => str_repeat('a', 256), // 256文字
            'is_public' => true,
        ];
        $response = $this->postJson('/api/v1/tasks', $data);
        $response->assertStatus(422)->assertJsonValidationErrors('title');
    }

    public function test_title_must_be_string_when_creating_task()
    {
        $data = [
            'title' => 12345, // 数値
            'is_public' => true,
        ];
        $response = $this->postJson('/api/v1/tasks', $data);
        $response->assertStatus(422)->assertJsonValidationErrors('title');
    }

    public function test_is_public_is_required_when_creating_task()
    {
        $data = [
            'title' => 'title only'
        ];
        $response = $this->postJson('/api/v1/tasks', $data);
        $response->assertStatus(422)->assertJsonValidationErrors('is_public');
    }

    public function test_is_public_must_be_boolean_when_creating_task()
    {
        $data = [
            'title' => 'Valid Title',
            'is_public' => 'not-boolean',
        ];
        $response = $this->postJson('/api/v1/tasks', $data);
        $response->assertStatus(422)->assertJsonValidationErrors('is_public');
    }

    public function test_description_can_be_null_but_must_be_string_when_present()
    {
        // 数値（不正値）
        $data = [
            'title' => 'desc num',
            'is_public' => true,
            'description' => 12345,
        ];
        $response = $this->postJson('/api/v1/tasks', $data);
        $response->assertStatus(422)->assertJsonValidationErrors('description');
    }

    public function test_expired_at_must_be_date_when_present()
    {
        $data = [
            'title' => 'date fail',
            'is_public' => true,
            'expired_at' => 'not-a-date',
        ];
        $response = $this->postJson('/api/v1/tasks', $data);
        $response->assertStatus(422)->assertJsonValidationErrors('expired_at');
    }

    public function test_assigned_user_ids_must_be_array_when_present()
    {
        $data = [
            'title' => 'assigned',
            'is_public' => true,
            'assigned_user_ids' => 'not-an-array',
        ];
        $response = $this->postJson('/api/v1/tasks', $data);
        $response->assertStatus(422)->assertJsonValidationErrors('assigned_user_ids');
    }

    public function test_assigned_user_ids_array_items_must_exist_user()
    {
        $data = [
            'title' => 'assigned invalid user',
            'is_public' => true,
            'assigned_user_ids' => [999999], // 存在しないID
        ];
        $response = $this->postJson('/api/v1/tasks', $data);
        $response->assertStatus(422)->assertJsonValidationErrors('assigned_user_ids.0');
    }

    public function test_assigned_user_ids_accepts_empty_array()
    {
        $data = [
            'title' => 'assigned empty',
            'is_public' => true,
            'assigned_user_ids' => [],
        ];
        $response = $this->postJson('/api/v1/tasks', $data);
        $response->assertStatus(201)->assertJsonPath('task.title', 'assigned empty');
    }
}

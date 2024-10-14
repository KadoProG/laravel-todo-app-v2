<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase; // データベースのリフレッシュ

    /** @test */
    public function it_can_get_all_tasks()
    {
        // テストデータの作成
        Task::factory()->count(3)->create();

        // APIリクエスト
        $response = $this->getJson('/api/v1/tasks');

        // ステータスコードとデータ構造の確認
        $response->assertStatus(200)
            ->assertJsonCount(3); // タスクが3つあるか確認
    }

    /** @test */
    public function it_can_get_a_single_task()
    {
        // テストデータの作成
        $task = Task::factory()->create();

        // APIリクエスト
        $response = $this->getJson("/api/v1/tasks/{$task->id}");

        // ステータスコードとデータ内容の確認
        $response->assertStatus(200)
            ->assertJson([
                'id' => $task->id,
                'title' => $task->title,
                'isDone' => $task->is_done, // キャメルケースで確認
            ]);
    }

    /** @test */
    public function it_can_create_a_task()
    {
        // テスト用データ
        $data = [
            'title' => 'New Task',
            'description' => 'This is a new task.',
        ];

        // APIリクエスト（POST）
        $response = $this->postJson('/api/v1/tasks', $data);

        // ステータスコードとレスポンスの確認
        $response->assertStatus(201)
            ->assertJson([
                'title' => 'New Task',
                'description' => 'This is a new task.',
                'isDone' => false, // キャメルケースで確認
            ]);

        // データベースに保存されているか確認
        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'is_done' => false, // スネークケースで確認
        ]);
    }

    /** @test */
    public function it_can_update_a_task()
    {
        // テストデータの作成
        $task = Task::factory()->create([
            'title' => 'Old Task',
            'is_done' => false,
        ]);

        // 更新用のデータ
        $data = [
            'title' => 'Updated Task',
            'isDone' => true, // キャメルケースで送信
        ];

        // APIリクエスト（PUT）
        $response = $this->putJson("/api/v1/tasks/{$task->id}", $data);

        // ステータスコードとレスポンスの確認
        $response->assertStatus(200)
            ->assertJson([
                'title' => 'Updated Task',
                'isDone' => true,
            ]);

        // データベースに反映されているか確認
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Task',
            'is_done' => true, // スネークケースで確認
        ]);
    }

    /** @test */
    public function it_can_delete_a_task()
    {
        // テストデータの作成
        $task = Task::factory()->create();

        // APIリクエスト（DELETE）
        $response = $this->deleteJson("/api/v1/tasks/{$task->id}");

        // ステータスコードの確認
        $response->assertStatus(204);

        // データベースから削除されているか確認
        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    /** @test */
    public function it_validates_title_field_when_creating_a_task()
    {
        // 空のデータでAPIリクエスト
        $response = $this->postJson('/api/v1/tasks', [
            'title' => '', // 空のタイトル
            'isDone' => false,
        ]);

        // バリデーションエラーの確認
        $response->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }
}

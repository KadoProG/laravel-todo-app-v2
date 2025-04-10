<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::factory()->createMany([[
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ], [
            'id' => 2,
            'name' => 'Test User2',
            'email' => 'test2@example.com',
            'password' => 'password',
        ]]);

        Task::factory()->createMany([
            [
                'id' => 1,
                'title' => 'タスク1',
                'description' => 'タスクの説明1',
                'is_public' => false,
                'is_done' => false,
                'expired_at' => '2025-04-11 18:00',
                'created_user_id' => 1,
            ],
            [
                'id' => 2,
                'title' => 'タスク2',
                'description' => 'タスクの説明2',
                'is_public' => false,
                'is_done' => false,
                'expired_at' => '2025-04-11 18:00',
                'created_user_id' => 2,
            ],
            [
                'id' => 3,
                'title' => 'タスク3',
                'description' => 'タスクの説明3',
                'is_public' => true,
                'is_done' => false,
                'expired_at' => '2025-04-11 18:00',
                'created_user_id' => 1,
            ],
            [
                'id' => 4,
                'title' => 'タスク4',
                'description' => 'タスクの説明4',
                'is_public' => true,
                'is_done' => false,
                'expired_at' => '2025-04-11 18:00',
                'created_user_id' => 1,
            ],
        ]);

        DB::table('task_assigned_users')->insert([
            [
                'id' => 1,
                'task_id' => 3,
                'user_id' => 1,
            ],
            [
                'id' => 2,
                'task_id' => 4,
                'user_id' => 2,
            ],
            [
                'id' => 3,
                'task_id' => 4,
                'user_id' => 1,
            ],
        ]);

    }
}

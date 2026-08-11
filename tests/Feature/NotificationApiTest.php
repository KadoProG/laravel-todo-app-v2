<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setup(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->user = $user;

        $token = JWTAuth::fromUser($user);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ]);
    }

    public function test_can_get_notifications()
    {
        Notification::factory()->count(3)->create(['user_id' => $this->user->id]);
        Notification::factory()->count(2)->create(); // 他ユーザーの通知

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'notifications')
            ->assertJson([
                'unread_count' => 3,
                'page' => 0,
                'size' => 20,
                'total_pages' => 1,
                'total_elements' => 3,
            ])
            ->assertJsonStructure([
                'notifications' => [
                    ['id', 'title', 'message', 'type', 'related_task_id', 'is_read', 'read_at', 'created_at'],
                ],
            ]);
    }

    public function test_notifications_are_paginated()
    {
        Notification::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/v1/notifications?page=1&size=2');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'notifications')
            ->assertJson([
                'page' => 1,
                'size' => 2,
                'total_pages' => 3,
                'total_elements' => 5,
            ]);
    }

    public function test_can_get_unread_count()
    {
        Notification::factory()->count(2)->create(['user_id' => $this->user->id]);
        Notification::factory()->count(3)->read()->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/v1/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJson(['unread_count' => 2]);
    }

    public function test_can_mark_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => NotificationType::TASK_ASSIGNED,
        ]);

        $response = $this->putJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJson([
                'notification' => [
                    'id' => $notification->id,
                    'type' => 'TASK_ASSIGNED',
                    'is_read' => true,
                ],
            ]);
        $this->assertNotNull($response->json('notification.read_at'));
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);
    }

    public function test_cannot_mark_other_users_notification_as_read()
    {
        $notification = Notification::factory()->create();

        $response = $this->putJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(403);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => false,
        ]);
    }

    public function test_mark_as_read_returns_404_for_unknown_notification()
    {
        $response = $this->putJson('/api/v1/notifications/999/read');

        $response->assertStatus(404);
    }

    public function test_can_mark_all_notifications_as_read()
    {
        Notification::factory()->count(3)->create(['user_id' => $this->user->id]);
        $otherNotification = Notification::factory()->create();

        $response = $this->putJson('/api/v1/notifications/read-all');

        $response->assertStatus(200)
            ->assertJson(['message' => 'All notifications marked as read']);
        $this->assertSame(0, Notification::where('user_id', $this->user->id)->unread()->count());
        $this->assertDatabaseHas('notifications', [
            'id' => $otherNotification->id,
            'is_read' => false,
        ]);
    }

    public function test_requires_authentication()
    {
        $response = $this->withHeaders(['Authorization' => ''])
            ->getJson('/api/v1/notifications');

        $response->assertStatus(401);
    }
}

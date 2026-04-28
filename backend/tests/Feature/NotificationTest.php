<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    private User $user;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->adminUser = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    }

    public function test_get_user_notifications(): void
    {
        // Create test notifications
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => Notification::TYPE_LOAN_APPROVED,
            'is_read' => false,
        ]);

        Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => Notification::TYPE_LOAN_DISBURSED,
            'is_read' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'unread_count',
        ]);
        $this->assertEquals(1, $response->json('unread_count'));
    }

    public function test_mark_notification_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200);
        $this->assertTrue($response->json('data.is_read'));
    }

    public function test_mark_all_notifications_as_read(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson('/api/notifications/read-all');

        $response->assertStatus(200);

        $unreadCount = Notification::where('user_id', $this->user->id)
            ->where('is_read', false)
            ->count();

        $this->assertEquals(0, $unreadCount);
    }

    public function test_delete_notification(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200);
        $this->assertNull(Notification::find($notification->id));
    }

    public function test_send_notification_admin_only(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/notifications/send', [
                'user_id' => $this->user->id,
                'type' => 'test_notification',
                'subject' => 'Test',
                'message' => 'Test message',
                'channel' => 'in_app',
            ]);

        $response->assertStatus(403);
    }

    public function test_send_notification_by_admin(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/notifications/send', [
                'user_id' => $this->user->id,
                'type' => 'test_notification',
                'subject' => 'Test Notification',
                'message' => 'This is a test notification',
                'channel' => 'in_app',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'user_id',
                'type',
                'subject',
                'message',
                'channel',
            ],
        ]);
    }

    public function test_cannot_mark_other_user_notification_as_read(): void
    {
        $otherUser = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $notification = Notification::factory()->create([
            'user_id' => $otherUser->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(403);
    }

    public function test_cannot_delete_other_user_notification(): void
    {
        $otherUser = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $notification = Notification::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(403);
    }

    public function test_notification_pagination(): void
    {
        Notification::factory()->count(30)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications?limit=10&offset=0');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data'));
    }
}

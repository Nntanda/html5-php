<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    private User $adminUser;
    private User $memberUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $this->memberUser = User::factory()->create(['role' => User::ROLE_MEMBER]);
    }

    public function test_get_audit_logs(): void
    {
        AuditLog::factory()->count(5)->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/audit-logs');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'pagination',
        ]);
    }

    public function test_non_admin_cannot_view_audit_logs(): void
    {
        $response = $this->actingAs($this->memberUser)
            ->getJson('/api/audit-logs');

        $response->assertStatus(403);
    }

    public function test_get_audit_log_by_id(): void
    {
        $log = AuditLog::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/audit-logs/{$log->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'user_id',
                'action',
                'entity_type',
                'entity_id',
                'changes',
                'ip_address',
                'created_at',
            ],
        ]);
    }

    public function test_filter_audit_logs_by_user(): void
    {
        $user = User::factory()->create();
        AuditLog::factory()->count(3)->create(['user_id' => $user->id]);
        AuditLog::factory()->count(2)->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/audit-logs?user_id={$user->id}");

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_filter_audit_logs_by_action(): void
    {
        AuditLog::factory()->count(2)->create(['action' => AuditLog::ACTION_CREATE]);
        AuditLog::factory()->count(3)->create(['action' => AuditLog::ACTION_UPDATE]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/audit-logs?action=' . AuditLog::ACTION_CREATE);

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_filter_audit_logs_by_date_range(): void
    {
        $startDate = now()->subDays(5);
        $endDate = now();

        AuditLog::factory()->create([
            'created_at' => $startDate->addDay(),
        ]);

        AuditLog::factory()->create([
            'created_at' => $startDate->subDays(10),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/audit-logs?start_date={$startDate->format('Y-m-d')}&end_date={$endDate->format('Y-m-d')}");

        $response->assertStatus(200);
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_get_audit_logs_for_entity(): void
    {
        $entityType = 'Loan';
        $entityId = 123;

        AuditLog::factory()->count(3)->create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);

        AuditLog::factory()->count(2)->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/audit-logs/entity/{$entityType}/{$entityId}");

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_get_audit_logs_for_user(): void
    {
        $user = User::factory()->create();
        AuditLog::factory()->count(4)->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/audit-logs/user/{$user->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'pagination',
        ]);
        $this->assertCount(4, $response->json('data'));
    }

    public function test_member_can_view_own_audit_logs(): void
    {
        AuditLog::factory()->count(2)->create(['user_id' => $this->memberUser->id]);

        $response = $this->actingAs($this->memberUser)
            ->getJson("/api/audit-logs/user/{$this->memberUser->id}");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_member_cannot_view_other_user_audit_logs(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->memberUser)
            ->getJson("/api/audit-logs/user/{$otherUser->id}");

        $response->assertStatus(403);
    }

    public function test_export_audit_logs(): void
    {
        AuditLog::factory()->count(5)->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/audit-logs/export');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
        ]);
    }

    public function test_audit_log_pagination(): void
    {
        AuditLog::factory()->count(100)->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/audit-logs?limit=20&offset=0');

        $response->assertStatus(200);
        $this->assertCount(20, $response->json('data'));
        $this->assertEquals(100, $response->json('pagination.total'));
    }
}

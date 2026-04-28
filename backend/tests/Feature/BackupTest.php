<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $loanOfficer;
    protected BackupService $backupService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $this->loanOfficer = User::factory()->create(['role' => User::ROLE_LOAN_OFFICER]);
        $this->backupService = app(BackupService::class);
    }

    /**
     * Test creating a manual backup via API
     */
    public function test_super_admin_can_create_manual_backup(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/backups/create', [
                'notes' => 'Test backup',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'filename',
                'file_size',
                'file_size_human',
                'status',
                'created_at',
                'backup_type',
            ],
        ]);

        $this->assertDatabaseHas('backups', [
            'backup_type' => Backup::TYPE_MANUAL,
            'status' => Backup::STATUS_COMPLETED,
        ]);
    }

    /**
     * Test non-super admin cannot create backup
     */
    public function test_non_super_admin_cannot_create_backup(): void
    {
        $response = $this->actingAs($this->loanOfficer)
            ->postJson('/api/backups/create', [
                'notes' => 'Test backup',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test listing backups
     */
    public function test_super_admin_can_list_backups(): void
    {
        Backup::factory()->count(5)->create();

        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/backups');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'filename',
                    'file_size',
                    'file_size_human',
                    'status',
                    'backup_type',
                    'created_by',
                    'created_at',
                    'notes',
                    'file_exists',
                ],
            ],
            'pagination',
        ]);

        $this->assertCount(5, $response->json('data'));
    }

    /**
     * Test filtering backups by status
     */
    public function test_can_filter_backups_by_status(): void
    {
        Backup::factory()->count(3)->completed()->create();
        Backup::factory()->count(2)->failed()->create();

        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/backups?status=' . Backup::STATUS_COMPLETED);

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test getting backup details
     */
    public function test_can_get_backup_details(): void
    {
        $backup = Backup::factory()->create();

        $response = $this->actingAs($this->superAdmin)
            ->getJson("/api/backups/{$backup->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'filename',
                'file_size',
                'file_size_human',
                'status',
                'backup_type',
                'created_by',
                'created_at',
                'notes',
                'file_exists',
            ],
        ]);
    }

    /**
     * Test getting non-existent backup returns 404
     */
    public function test_getting_non_existent_backup_returns_404(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/backups/999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Backup not found',
        ]);
    }

    /**
     * Test deleting a backup
     */
    public function test_super_admin_can_delete_backup(): void
    {
        $backup = Backup::factory()->create();

        $response = $this->actingAs($this->superAdmin)
            ->deleteJson("/api/backups/{$backup->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Backup deleted successfully',
        ]);

        $this->assertDatabaseMissing('backups', ['id' => $backup->id]);
    }

    /**
     * Test getting backup statistics
     */
    public function test_can_get_backup_statistics(): void
    {
        Backup::factory()->count(3)->completed()->create();
        Backup::factory()->count(1)->failed()->create();

        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/backups/stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_backups',
                'completed_backups',
                'failed_backups',
                'total_size',
                'total_size_human',
                'last_backup',
            ],
        ]);

        $this->assertEquals(4, $response->json('data.total_backups'));
        $this->assertEquals(3, $response->json('data.completed_backups'));
        $this->assertEquals(1, $response->json('data.failed_backups'));
    }

    /**
     * Test backup validation
     */
    public function test_backup_creation_validates_notes(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/backups/create', [
                'notes' => str_repeat('a', 501), // Exceeds max length
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('notes');
    }

    /**
     * Test pagination of backups
     */
    public function test_backups_are_paginated(): void
    {
        Backup::factory()->count(20)->create();

        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/backups?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(20, $response->json('pagination.total'));
        $this->assertEquals(2, $response->json('pagination.last_page'));
    }

    /**
     * Test backup model relationships
     */
    public function test_backup_has_creator_relationship(): void
    {
        $backup = Backup::factory()->create(['created_by' => $this->superAdmin->id]);

        $this->assertNotNull($backup->createdBy);
        $this->assertEquals($this->superAdmin->id, $backup->createdBy->id);
    }

    /**
     * Test backup human readable size
     */
    public function test_backup_human_readable_size(): void
    {
        $backup = Backup::factory()->create(['file_size' => 1024 * 1024]); // 1 MB

        $this->assertStringContainsString('MB', $backup->human_readable_size);
    }

    /**
     * Test backup status constants
     */
    public function test_backup_status_constants(): void
    {
        $this->assertEquals('pending', Backup::STATUS_PENDING);
        $this->assertEquals('completed', Backup::STATUS_COMPLETED);
        $this->assertEquals('failed', Backup::STATUS_FAILED);
        $this->assertEquals('restored', Backup::STATUS_RESTORED);
    }

    /**
     * Test backup type constants
     */
    public function test_backup_type_constants(): void
    {
        $this->assertEquals('manual', Backup::TYPE_MANUAL);
        $this->assertEquals('scheduled', Backup::TYPE_SCHEDULED);
    }
}

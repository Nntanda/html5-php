<?php

namespace Tests\Unit;

use App\Models\Backup;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BackupService $backupService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupService = app(BackupService::class);
        $this->user = User::factory()->create();
    }

    /**
     * Test backup service can be instantiated
     */
    public function test_backup_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(BackupService::class, $this->backupService);
    }

    /**
     * Test backup directory is created
     */
    public function test_backup_directory_is_created(): void
    {
        $backupDir = storage_path('backups');
        $this->assertTrue(is_dir($backupDir));
    }

    /**
     * Test get all backups
     */
    public function test_get_all_backups(): void
    {
        Backup::factory()->count(5)->create();

        $backups = $this->backupService->getAllBackups();

        $this->assertCount(5, $backups);
    }

    /**
     * Test get backup by ID
     */
    public function test_get_backup_by_id(): void
    {
        $backup = Backup::factory()->create();

        $retrieved = $this->backupService->getBackupById($backup->id);

        $this->assertNotNull($retrieved);
        $this->assertEquals($backup->id, $retrieved->id);
    }

    /**
     * Test get non-existent backup returns null
     */
    public function test_get_non_existent_backup_returns_null(): void
    {
        $retrieved = $this->backupService->getBackupById(999);

        $this->assertNull($retrieved);
    }

    /**
     * Test delete backup
     */
    public function test_delete_backup(): void
    {
        $backup = Backup::factory()->create();

        $result = $this->backupService->deleteBackup($backup);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('backups', ['id' => $backup->id]);
    }

    /**
     * Test clean old backups keeps specified count
     */
    public function test_clean_old_backups_keeps_specified_count(): void
    {
        Backup::factory()->count(15)->create();

        $deleted = $this->backupService->cleanOldBackups(10);

        $this->assertEquals(5, $deleted);
        $this->assertCount(10, Backup::all());
    }

    /**
     * Test get backup statistics
     */
    public function test_get_backup_statistics(): void
    {
        Backup::factory()->count(3)->completed()->create(['file_size' => 1000000]);
        Backup::factory()->count(1)->failed()->create(['file_size' => 500000]);

        $stats = $this->backupService->getBackupStats();

        $this->assertEquals(4, $stats['total_backups']);
        $this->assertEquals(3, $stats['completed_backups']);
        $this->assertEquals(1, $stats['failed_backups']);
        $this->assertEquals(3500000, $stats['total_size']);
        $this->assertNotNull($stats['last_backup']);
    }

    /**
     * Test backup statistics with no backups
     */
    public function test_backup_statistics_with_no_backups(): void
    {
        $stats = $this->backupService->getBackupStats();

        $this->assertEquals(0, $stats['total_backups']);
        $this->assertEquals(0, $stats['completed_backups']);
        $this->assertEquals(0, $stats['failed_backups']);
        $this->assertEquals(0, $stats['total_size']);
    }

    /**
     * Test backup model file exists check
     */
    public function test_backup_file_exists_check(): void
    {
        $backup = Backup::factory()->create([
            'file_path' => storage_path('backups/nonexistent.sql'),
        ]);

        $this->assertFalse($backup->fileExists());
    }

    /**
     * Test backup model relationships
     */
    public function test_backup_created_by_relationship(): void
    {
        $backup = Backup::factory()->create(['created_by' => $this->user->id]);

        $this->assertNotNull($backup->createdBy);
        $this->assertEquals($this->user->id, $backup->createdBy->id);
    }

    /**
     * Test backup with null creator
     */
    public function test_backup_with_null_creator(): void
    {
        $backup = Backup::factory()->create(['created_by' => null]);

        $this->assertNull($backup->createdBy);
    }

    /**
     * Test backup status transitions
     */
    public function test_backup_status_transitions(): void
    {
        $backup = Backup::factory()->create(['status' => Backup::STATUS_PENDING]);

        $this->assertEquals(Backup::STATUS_PENDING, $backup->status);

        $backup->update(['status' => Backup::STATUS_COMPLETED]);
        $this->assertEquals(Backup::STATUS_COMPLETED, $backup->status);

        $backup->update(['status' => Backup::STATUS_RESTORED]);
        $this->assertEquals(Backup::STATUS_RESTORED, $backup->status);
    }

    /**
     * Test backup type filtering
     */
    public function test_backup_type_filtering(): void
    {
        Backup::factory()->count(3)->manual()->create();
        Backup::factory()->count(2)->scheduled()->create();

        $manual = Backup::where('backup_type', Backup::TYPE_MANUAL)->get();
        $scheduled = Backup::where('backup_type', Backup::TYPE_SCHEDULED)->get();

        $this->assertCount(3, $manual);
        $this->assertCount(2, $scheduled);
    }

    /**
     * Test backup file size casting
     */
    public function test_backup_file_size_casting(): void
    {
        $backup = Backup::factory()->create(['file_size' => 1024]);

        $this->assertIsInt($backup->file_size);
        $this->assertEquals(1024, $backup->file_size);
    }

    /**
     * Test backup timestamps
     */
    public function test_backup_timestamps(): void
    {
        $backup = Backup::factory()->create();

        $this->assertNotNull($backup->created_at);
        $this->assertNotNull($backup->updated_at);
    }

    /**
     * Test backup factory states
     */
    public function test_backup_factory_states(): void
    {
        $completed = Backup::factory()->completed()->create();
        $failed = Backup::factory()->failed()->create();
        $manual = Backup::factory()->manual()->create();
        $scheduled = Backup::factory()->scheduled()->create();

        $this->assertEquals(Backup::STATUS_COMPLETED, $completed->status);
        $this->assertEquals(Backup::STATUS_FAILED, $failed->status);
        $this->assertEquals(Backup::TYPE_MANUAL, $manual->backup_type);
        $this->assertEquals(Backup::TYPE_SCHEDULED, $scheduled->backup_type);
    }
}

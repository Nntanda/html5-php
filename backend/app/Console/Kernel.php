<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Daily backup at 2 AM
        $schedule->command('backup:run --type=scheduled')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('Scheduled backup failed');
            })
            ->onSuccess(function () {
                \Log::info('Scheduled backup completed successfully');
            });

        // Check for overdue loans daily at 6 AM
        $schedule->command('loans:mark-overdue')
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('Overdue loan check failed');
            })
            ->onSuccess(function () {
                \Log::info('Overdue loan check completed');
            });

        // Weekly cleanup of old backups (keep last 10) - every Sunday at 3 AM
        $schedule->call(function () {
            $backupService = app(\App\Services\BackupService::class);
            $deleted = $backupService->cleanOldBackups(10);
            \Log::info("Backup cleanup completed. Deleted {$deleted} old backups.");
        })
            ->weeklyOn(0, '03:00')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}

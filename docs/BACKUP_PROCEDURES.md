# Database Backup Procedures

## Overview

The SACCO Management System includes comprehensive database backup functionality to ensure data protection and disaster recovery. This document describes the backup system, procedures, and best practices.

## Features

- **Automated Scheduled Backups**: Daily backups at 2 AM
- **Manual Backups**: On-demand backup creation via API or CLI
- **Backup Management**: List, view, and delete backups
- **Restore Functionality**: Restore database from any backup
- **Backup Tracking**: Database records of all backups with metadata
- **Automatic Cleanup**: Weekly cleanup to maintain only the last 10 backups
- **Statistics**: View backup statistics and storage usage

## Architecture

### Components

1. **Backup Model** (`app/Models/Backup.php`)
   - Tracks all backups in the database
   - Stores metadata: filename, size, status, type, creator
   - Relationships with User model

2. **BackupService** (`app/Services/BackupService.php`)
   - Core backup operations
   - Uses Laravel's Process facade to execute mysqldump
   - Handles backup creation, restoration, and cleanup

3. **BackupController** (`app/Http/Controllers/BackupController.php`)
   - REST API endpoints for backup management
   - Super Admin only access
   - Endpoints for create, list, restore, delete, and statistics

4. **Artisan Command** (`app/Console/Commands/BackupRun.php`)
   - CLI command for manual backups
   - Supports both manual and scheduled backup types

5. **Console Kernel** (`app/Console/Kernel.php`)
   - Schedules daily backups at 2 AM
   - Schedules weekly cleanup at 3 AM on Sundays
   - Logs backup operations

## API Endpoints

All backup endpoints require Super Admin role and authentication.

### Create Manual Backup

```
POST /api/backups/create
```

**Request:**
```json
{
  "notes": "Optional backup notes"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Backup created successfully",
  "data": {
    "id": 1,
    "filename": "backup_2024-01-15_02-00-00.sql",
    "file_size": 5242880,
    "file_size_human": "5 MB",
    "status": "completed",
    "created_at": "2024-01-15T02:00:00Z",
    "backup_type": "manual"
  }
}
```

### List Backups

```
GET /api/backups?per_page=15&status=completed
```

**Query Parameters:**
- `per_page`: Number of backups per page (default: 15)
- `status`: Filter by status (pending, completed, failed, restored)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "filename": "backup_2024-01-15_02-00-00.sql",
      "file_size": 5242880,
      "file_size_human": "5 MB",
      "status": "completed",
      "backup_type": "manual",
      "created_by": "Admin User",
      "created_at": "2024-01-15T02:00:00Z",
      "notes": "Manual backup",
      "file_exists": true
    }
  ],
  "pagination": {
    "total": 25,
    "per_page": 15,
    "current_page": 1,
    "last_page": 2
  }
}
```

### Get Backup Details

```
GET /api/backups/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "filename": "backup_2024-01-15_02-00-00.sql",
    "file_size": 5242880,
    "file_size_human": "5 MB",
    "status": "completed",
    "backup_type": "manual",
    "created_by": "Admin User",
    "created_at": "2024-01-15T02:00:00Z",
    "notes": "Manual backup",
    "file_exists": true
  }
}
```

### Restore from Backup

```
POST /api/backups/{id}/restore
```

**Response:**
```json
{
  "success": true,
  "message": "Backup restored successfully",
  "data": {
    "id": 1,
    "filename": "backup_2024-01-15_02-00-00.sql",
    "status": "restored",
    "restored_at": "2024-01-15T10:30:00Z"
  }
}
```

### Delete Backup

```
DELETE /api/backups/{id}
```

**Response:**
```json
{
  "success": true,
  "message": "Backup deleted successfully"
}
```

### Get Backup Statistics

```
GET /api/backups/stats
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_backups": 25,
    "completed_backups": 23,
    "failed_backups": 2,
    "total_size": 131072000,
    "total_size_human": "125 MB",
    "last_backup": "2024-01-15T02:00:00Z"
  }
}
```

## CLI Commands

### Create Manual Backup

```bash
php artisan backup:run --type=manual
```

**Output:**
```
Starting database backup...
✓ Backup created successfully!
  Filename: backup_2024-01-15_10-30-45.sql
  Size: 5.2 MB
  Status: completed
```

### Create Scheduled Backup

```bash
php artisan backup:run --type=scheduled
```

## Backup Scheduling

### Daily Backup

- **Time**: 2:00 AM daily
- **Type**: Scheduled
- **Retention**: Last 10 backups kept
- **Logging**: All backups logged to Laravel logs

### Weekly Cleanup

- **Time**: 3:00 AM every Sunday
- **Action**: Deletes backups older than the last 10
- **Logging**: Cleanup results logged to Laravel logs

### Configuration

Edit `app/Console/Kernel.php` to modify backup schedule:

```php
// Daily backup at 2 AM
$schedule->command('backup:run --type=scheduled')
    ->dailyAt('02:00')
    ->withoutOverlapping();

// Weekly cleanup at 3 AM on Sundays
$schedule->call(function () {
    $backupService = app(\App\Services\BackupService::class);
    $deleted = $backupService->cleanOldBackups(10);
    \Log::info("Backup cleanup completed. Deleted {$deleted} old backups.");
})
    ->weeklyOn(0, '03:00')
    ->withoutOverlapping();
```

## Backup Storage

### Location

Backups are stored in: `storage/backups/`

### File Naming

Backups follow the naming convention: `backup_YYYY-MM-DD_HH-MM-SS.sql`

Example: `backup_2024-01-15_02-00-00.sql`

### File Size

Backup file size depends on database size. Typical sizes:
- Small database (< 100 MB): 5-20 MB
- Medium database (100-500 MB): 20-100 MB
- Large database (> 500 MB): 100+ MB

## Backup Status

### Status Types

- **pending**: Backup creation in progress
- **completed**: Backup successfully created
- **failed**: Backup creation failed
- **restored**: Backup has been restored

## Backup Types

- **manual**: Created manually via API or CLI
- **scheduled**: Created automatically by scheduler

## Security Considerations

### Access Control

- Only Super Admin users can access backup endpoints
- All backup operations are logged in audit logs
- Backup creation and restoration are tracked with user information

### File Permissions

- Backup files are stored with restricted permissions
- Only the application server can read/write backup files
- Backup directory should be outside web root

### Encryption

For production environments, consider:
- Encrypting backup files at rest
- Using encrypted storage for backup directory
- Implementing backup file encryption in BackupService

### Backup Verification

Always verify backups:
1. Check backup file exists and has reasonable size
2. Test restore on staging environment before production
3. Monitor backup logs for failures

## Disaster Recovery

### Restore Procedure

1. **Verify Backup**
   ```bash
   ls -lh storage/backups/
   ```

2. **Check Backup Status**
   ```
   GET /api/backups/{id}
   ```

3. **Restore from API**
   ```
   POST /api/backups/{id}/restore
   ```

4. **Verify Data**
   - Check database integrity
   - Verify critical data is present
   - Test application functionality

### Manual Restore (if API unavailable)

```bash
mysql -h localhost -u username -p database_name < storage/backups/backup_2024-01-15_02-00-00.sql
```

## Monitoring and Maintenance

### Check Backup Status

```
GET /api/backups?status=failed
```

### View Backup Statistics

```
GET /api/backups/stats
```

### Monitor Logs

```bash
tail -f storage/logs/laravel.log | grep -i backup
```

### Disk Space Management

Monitor backup directory size:
```bash
du -sh storage/backups/
```

Automatic cleanup runs weekly to maintain only last 10 backups.

## Troubleshooting

### Backup Creation Fails

**Error**: "Backup command failed"

**Solutions**:
1. Verify MySQL/MariaDB is running
2. Check database credentials in `.env`
3. Ensure `mysqldump` is installed
4. Check disk space availability
5. Verify backup directory permissions

### Restore Fails

**Error**: "Restore command failed"

**Solutions**:
1. Verify backup file exists
2. Check backup file integrity
3. Ensure MySQL is running
4. Verify database credentials
5. Check for active connections to database

### Backup File Not Found

**Error**: "Backup file does not exist"

**Solutions**:
1. Verify backup was completed successfully
2. Check backup directory path
3. Verify file permissions
4. Check disk space

## Best Practices

1. **Regular Testing**: Test restore procedures regularly
2. **Offsite Backups**: Copy backups to offsite storage
3. **Monitoring**: Monitor backup logs and statistics
4. **Documentation**: Document backup procedures
5. **Retention Policy**: Define and implement backup retention
6. **Encryption**: Encrypt backups for sensitive data
7. **Verification**: Verify backup integrity regularly
8. **Automation**: Use scheduled backups for consistency
9. **Alerting**: Set up alerts for backup failures
10. **Capacity Planning**: Monitor backup storage growth

## Performance Impact

- **Backup Duration**: Typically 5-30 minutes depending on database size
- **CPU Usage**: Moderate during backup
- **Disk I/O**: High during backup
- **Network**: Minimal impact
- **Recommended Time**: Schedule during low-traffic periods

## Backup Retention Policy

Default policy:
- Keep last 10 backups
- Automatic cleanup weekly
- Manual backups can be kept indefinitely

Customize in `BackupService::cleanOldBackups()`:
```php
$backupService->cleanOldBackups(20); // Keep last 20 backups
```

## Support and Troubleshooting

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Review backup status: `GET /api/backups`
3. Check disk space: `df -h`
4. Verify MySQL connectivity: `mysql -u user -p -e "SELECT 1"`

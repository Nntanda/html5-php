# Backup Management - Quick Reference

## Quick Start

### Create Manual Backup (API)
```bash
curl -X POST http://localhost:8000/api/backups/create \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"notes": "Pre-deployment backup"}'
```

### Create Manual Backup (CLI)
```bash
php artisan backup:run --type=manual
```

### List Backups
```bash
curl -X GET http://localhost:8000/api/backups \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Restore Backup
```bash
curl -X POST http://localhost:8000/api/backups/{id}/restore \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Delete Backup
```bash
curl -X DELETE http://localhost:8000/api/backups/{id} \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Statistics
```bash
curl -X GET http://localhost:8000/api/backups/stats \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Backup Status

| Status | Meaning |
|--------|---------|
| pending | Backup in progress |
| completed | Backup successful |
| failed | Backup failed |
| restored | Backup has been restored |

## Backup Types

| Type | Source |
|------|--------|
| manual | Created via API or CLI |
| scheduled | Created automatically |

## Automatic Schedules

| Task | Time | Frequency |
|------|------|-----------|
| Database Backup | 2:00 AM | Daily |
| Cleanup Old Backups | 3:00 AM | Weekly (Sunday) |

## File Locations

- **Backups**: `storage/backups/`
- **Logs**: `storage/logs/laravel.log`
- **Config**: `app/Console/Kernel.php`

## Common Tasks

### Check Last Backup
```bash
curl -X GET "http://localhost:8000/api/backups?per_page=1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Filter by Status
```bash
curl -X GET "http://localhost:8000/api/backups?status=completed" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Manual Restore (Database)
```bash
mysql -u root -p database_name < storage/backups/backup_2024-01-15_02-00-00.sql
```

### Check Backup Size
```bash
du -sh storage/backups/
```

### View Recent Backups
```bash
ls -lh storage/backups/ | tail -10
```

## Troubleshooting

### Backup Failed
1. Check logs: `tail -f storage/logs/laravel.log`
2. Verify MySQL: `mysql -u root -p -e "SELECT 1"`
3. Check disk space: `df -h`

### Restore Failed
1. Verify backup exists: `ls -l storage/backups/`
2. Check MySQL running: `systemctl status mysql`
3. Check credentials in `.env`

### Disk Space Issues
1. Check backup size: `du -sh storage/backups/`
2. Delete old backups: `DELETE FROM backups WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)`
3. Run cleanup: `php artisan backup:cleanup`

## API Response Examples

### Success Response
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
    "backup_type": "manual"
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Failed to create backup: Error message"
}
```

## Requirements

- Super Admin role required
- MySQL/MariaDB running
- `mysqldump` installed
- Sufficient disk space
- Write permissions on `storage/backups/`

## Performance Tips

1. Schedule backups during low-traffic periods
2. Monitor backup duration
3. Keep only necessary backups
4. Test restores regularly
5. Monitor disk space usage

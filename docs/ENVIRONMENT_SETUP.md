# Environment Setup Guide

This guide explains how to configure environment variables for different deployment scenarios.

## Table of Contents

1. [Backend Environment](#backend-environment)
2. [Frontend Environment](#frontend-environment)
3. [Environment-Specific Configuration](#environment-specific-configuration)
4. [Security Best Practices](#security-best-practices)

## Backend Environment

The backend uses Laravel's `.env` file for configuration. Copy `.env.example` to `.env` and configure the following:

### Core Application Settings

```env
# Application name displayed in emails and notifications
APP_NAME="SACCO Management System"

# Environment: local, staging, production
APP_ENV=production

# Application key (generate with: php artisan key:generate)
APP_KEY=base64:...

# Debug mode (NEVER enable in production)
APP_DEBUG=false

# Application URL
APP_URL=https://api.sacco.example.com
```

### Database Configuration

```env
# Database connection type
DB_CONNECTION=mysql

# Database host (127.0.0.1 for local, IP/hostname for remote)
DB_HOST=127.0.0.1

# Database port (default MySQL: 3306)
DB_PORT=3306

# Database name
DB_DATABASE=sacco_production

# Database credentials
DB_USERNAME=sacco_user
DB_PASSWORD=strong_password_here
```

**Database Best Practices:**
- Use strong passwords (minimum 16 characters, mixed case, numbers, symbols)
- Create dedicated database user with limited privileges
- Never use root user in production
- Enable SSL for remote database connections

### Cache and Session

```env
# Cache driver: file, redis, memcached
CACHE_DRIVER=redis

# Session driver: file, cookie, database, redis
SESSION_DRIVER=redis

# Session lifetime in minutes
SESSION_LIFETIME=120

# Redis configuration (if using redis)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**Recommendations:**
- **Development**: Use `file` driver
- **Production**: Use `redis` for better performance
- **Offline**: Use `file` or `database` driver

### Queue Configuration

```env
# Queue connection: sync, database, redis
QUEUE_CONNECTION=redis
```

**Recommendations:**
- **Development**: Use `sync` (processes immediately)
- **Production**: Use `redis` or `database`
- **Offline**: Use `database`

### Mail Configuration

#### Production (Online)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@sacco.example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

#### Development/Offline

```env
MAIL_MAILER=log
MAIL_HOST=localhost
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@sacco.local"
MAIL_FROM_NAME="${APP_NAME}"
```

**Popular Mail Services:**
- **Gmail**: smtp.gmail.com:587 (requires app password)
- **SendGrid**: smtp.sendgrid.net:587
- **Mailgun**: smtp.mailgun.org:587
- **Amazon SES**: email-smtp.region.amazonaws.com:587

### SMS Configuration

#### Production (Online)

```env
SMS_ENABLED=true
SMS_PROVIDER=africastalking
SMS_API_KEY=your_api_key_here
SMS_USERNAME=your_username
SMS_SENDER_ID=SACCO
```

#### Development/Offline

```env
SMS_ENABLED=false
```

**Supported SMS Providers:**
- Africa's Talking
- Twilio
- Nexmo/Vonage

### Authentication (Sanctum)

```env
# Comma-separated list of frontend domains
SANCTUM_STATEFUL_DOMAINS=admin.sacco.example.com,portal.sacco.example.com,localhost:5173,localhost:5174
```

**Important:**
- Include all frontend domains that will access the API
- Include localhost ports for development
- No protocol (http/https) needed

### Logging

```env
# Log channel: stack, single, daily, slack, syslog
LOG_CHANNEL=daily

# Log level: debug, info, notice, warning, error, critical, alert, emergency
LOG_LEVEL=error
```

**Recommendations:**
- **Development**: `LOG_LEVEL=debug`
- **Production**: `LOG_LEVEL=error`

### CORS Configuration

```env
# Comma-separated list of allowed origins
CORS_ALLOWED_ORIGINS=https://admin.sacco.example.com,https://portal.sacco.example.com
```

For development, you can use `*` but NEVER in production:
```env
CORS_ALLOWED_ORIGINS=*
```

## Frontend Environment

Both admin-app and client-portal use Vite's environment variables.

### Development (.env)

```env
VITE_API_URL=http://localhost:8000/api
```

### Production (.env.production)

```env
VITE_API_URL=https://api.sacco.example.com/api
```

**Important Notes:**
- Vite environment variables must start with `VITE_`
- Changes require rebuild: `npm run build`
- Variables are embedded at build time (not runtime)

## Environment-Specific Configuration

### Local Development

**Backend (.env):**
```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_HOST=127.0.0.1
DB_DATABASE=sacco_dev

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file

MAIL_MAILER=log
SMS_ENABLED=false

SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:5174
CORS_ALLOWED_ORIGINS=*
```

**Frontend (.env):**
```env
VITE_API_URL=http://localhost:8000/api
```

### Staging Environment

**Backend (.env):**
```env
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://api-staging.sacco.example.com

DB_HOST=staging-db.example.com
DB_DATABASE=sacco_staging

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

MAIL_MAILER=smtp
SMS_ENABLED=true

SANCTUM_STATEFUL_DOMAINS=admin-staging.sacco.example.com,portal-staging.sacco.example.com
CORS_ALLOWED_ORIGINS=https://admin-staging.sacco.example.com,https://portal-staging.sacco.example.com
```

**Frontend (.env.production):**
```env
VITE_API_URL=https://api-staging.sacco.example.com/api
```

### Production Environment

**Backend (.env):**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.sacco.example.com

DB_HOST=production-db.example.com
DB_DATABASE=sacco_production

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

MAIL_MAILER=smtp
SMS_ENABLED=true

SANCTUM_STATEFUL_DOMAINS=admin.sacco.example.com,portal.sacco.example.com
CORS_ALLOWED_ORIGINS=https://admin.sacco.example.com,https://portal.sacco.example.com

LOG_LEVEL=error
```

**Frontend (.env.production):**
```env
VITE_API_URL=https://api.sacco.example.com/api
```

### Offline/Local Deployment

**Backend (.env):**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8000

DB_HOST=127.0.0.1
DB_DATABASE=sacco_offline

CACHE_DRIVER=file
QUEUE_CONNECTION=database
SESSION_DRIVER=file

MAIL_MAILER=log
SMS_ENABLED=false

SANCTUM_STATEFUL_DOMAINS=localhost:4173,localhost:4174
CORS_ALLOWED_ORIGINS=http://localhost:4173,http://localhost:4174
```

**Frontend (.env):**
```env
VITE_API_URL=http://localhost:8000/api
```

## Security Best Practices

### 1. Never Commit .env Files

Add to `.gitignore`:
```
.env
.env.*
!.env.example
```

### 2. Use Strong Passwords

Generate strong passwords:
```bash
# Random 32-character password
openssl rand -base64 32

# Or use Laravel's helper
php artisan tinker
>>> Str::random(32)
```

### 3. Rotate Secrets Regularly

- Change `APP_KEY` after major updates
- Rotate database passwords quarterly
- Update API keys when compromised

### 4. Limit Debug Mode

**NEVER** enable `APP_DEBUG=true` in production:
- Exposes sensitive information
- Shows stack traces to users
- Reveals file paths and configuration

### 5. Secure File Permissions

```bash
# Backend
chmod 644 .env
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Prevent web access to .env
# Add to .htaccess or nginx config
```

### 6. Use Environment-Specific Keys

Don't reuse the same `APP_KEY` across environments:
```bash
# Generate new key for each environment
php artisan key:generate
```

### 7. Validate Configuration

```bash
# Check configuration
php artisan config:show

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Test mail configuration
php artisan tinker
>>> Mail::raw('Test', function($msg) { $msg->to('test@example.com'); });
```

## Troubleshooting

### Configuration Cache Issues

If changes to `.env` don't take effect:

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches (production only)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Frontend Environment Not Loading

```bash
# Rebuild frontend
npm run build

# Check if variables are embedded
grep -r "VITE_API_URL" dist/
```

### Database Connection Failed

1. Verify credentials: `mysql -u username -p -h host database`
2. Check MySQL is running: `systemctl status mysql`
3. Verify host/port in `.env`
4. Check firewall rules

### CORS Errors

1. Verify `SANCTUM_STATEFUL_DOMAINS` includes frontend domain
2. Check `CORS_ALLOWED_ORIGINS` configuration
3. Ensure frontend uses correct API URL
4. Clear browser cache

## Environment Variables Reference

### Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `APP_KEY` | Application encryption key | `base64:...` |
| `DB_DATABASE` | Database name | `sacco_production` |
| `DB_USERNAME` | Database user | `sacco_user` |
| `DB_PASSWORD` | Database password | `strong_password` |

### Optional Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_DEBUG` | `false` | Enable debug mode |
| `LOG_LEVEL` | `error` | Logging level |
| `CACHE_DRIVER` | `file` | Cache storage driver |
| `QUEUE_CONNECTION` | `sync` | Queue driver |
| `MAIL_MAILER` | `log` | Mail driver |
| `SMS_ENABLED` | `false` | Enable SMS notifications |

---

**Last Updated**: 2024
**Version**: 1.0

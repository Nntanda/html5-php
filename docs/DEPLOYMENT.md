# SACCO Management System - Deployment Guide

This guide covers deployment options for the SACCO Management System, including both online (cloud) and offline (local) deployment scenarios.

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Offline/Local Deployment](#offlinelocal-deployment)
3. [Online/Cloud Deployment](#onlinecloud-deployment)
4. [Environment Configuration](#environment-configuration)
5. [Database Migration](#database-migration)
6. [Troubleshooting](#troubleshooting)

## System Requirements

### Backend Requirements
- PHP 8.1 or higher
- Composer 2.x
- MySQL 8.0+ or MariaDB 10.5+
- PHP Extensions:
  - BCMath
  - Ctype
  - Fileinfo
  - JSON
  - Mbstring
  - OpenSSL
  - PDO
  - Tokenizer
  - XML

### Frontend Requirements
- Node.js 18.x or higher
- npm 9.x or higher

### Server Requirements (Production)
- Minimum 2GB RAM
- 10GB disk space
- Apache 2.4+ or Nginx 1.18+

## Offline/Local Deployment

Offline deployment is ideal for SACCOs operating in areas with limited internet connectivity or those requiring complete data sovereignty.

### Quick Start

#### Linux/Mac

```bash
# Make the script executable
chmod +x deploy-offline.sh

# Run the deployment script
./deploy-offline.sh
```

#### Windows

```powershell
# Run PowerShell as Administrator
# Set execution policy (if needed)
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser

# Run the deployment script
.\deploy-offline.ps1
```

### Manual Offline Deployment

If you prefer manual setup or the automated script fails:

#### 1. Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE sacco_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

#### 2. Backend Setup

```bash
cd backend

# Copy environment file
cp .env.example .env

# Edit .env file with your database credentials
nano .env

# Install dependencies
composer install --no-dev --optimize-autoloader

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# (Optional) Seed with sample data
php artisan db:seed

# Create storage link
php artisan storage:link

# Set permissions
chmod -R 775 storage bootstrap/cache
```

#### 3. Admin Application Setup

```bash
cd admin-app

# Create environment file
echo "VITE_API_URL=http://localhost:8000/api" > .env

# Install dependencies
npm install

# Build for production
npm run build
```

#### 4. Client Portal Setup

```bash
cd client-portal

# Create environment file
echo "VITE_API_URL=http://localhost:8000/api" > .env

# Install dependencies
npm install

# Build for production
npm run build
```

#### 5. Start Services

```bash
# Terminal 1: Start backend API
cd backend
php artisan serve

# Terminal 2: Serve admin app
cd admin-app
npm run preview

# Terminal 3: Serve client portal
cd client-portal
npm run preview
```

### Offline Configuration Notes

- **Mail**: Set `MAIL_MAILER=log` to log emails instead of sending them
- **SMS**: Set `SMS_ENABLED=false` to disable SMS notifications
- **Queue**: Use `QUEUE_CONNECTION=database` for background jobs
- **Cache**: Use `CACHE_DRIVER=file` for simple file-based caching

## Online/Cloud Deployment

### Prerequisites

- Domain name (e.g., sacco.example.com)
- SSL certificate (Let's Encrypt recommended)
- Cloud server (DigitalOcean, AWS, Azure, etc.)
- Email service (SMTP, SendGrid, Mailgun, etc.)
- SMS service (Twilio, Africa's Talking, etc.)

### Deployment Steps

#### 1. Server Setup

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-xml php8.1-mbstring \
  php8.1-curl php8.1-zip php8.1-bcmath nginx mysql-server

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

#### 2. Database Setup

```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql
CREATE DATABASE sacco_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sacco_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON sacco_production.* TO 'sacco_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### 3. Deploy Backend

```bash
# Clone or upload your code
cd /var/www
sudo git clone <your-repo-url> sacco
cd sacco/backend

# Set ownership
sudo chown -R www-data:www-data /var/www/sacco

# Install dependencies
composer install --no-dev --optimize-autoloader

# Configure environment
cp .env.example .env
nano .env  # Edit with production settings

# Generate key and run migrations
php artisan key:generate
php artisan migrate --force
php artisan storage:link

# Set permissions
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

#### 4. Configure Nginx

```bash
sudo nano /etc/nginx/sites-available/sacco-api
```

```nginx
server {
    listen 80;
    server_name api.sacco.example.com;
    root /var/www/sacco/backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/sacco-api /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

#### 5. Deploy Frontend Applications

```bash
# Build admin app
cd /var/www/sacco/admin-app
echo "VITE_API_URL=https://api.sacco.example.com/api" > .env
npm install
npm run build

# Build client portal
cd /var/www/sacco/client-portal
echo "VITE_API_URL=https://api.sacco.example.com/api" > .env
npm install
npm run build
```

#### 6. Configure Nginx for Frontend

```bash
sudo nano /etc/nginx/sites-available/sacco-admin
```

```nginx
server {
    listen 80;
    server_name admin.sacco.example.com;
    root /var/www/sacco/admin-app/dist;

    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

```bash
sudo nano /etc/nginx/sites-available/sacco-client
```

```nginx
server {
    listen 80;
    server_name portal.sacco.example.com;
    root /var/www/sacco/client-portal/dist;

    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

```bash
# Enable sites
sudo ln -s /etc/nginx/sites-available/sacco-admin /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/sacco-client /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

#### 7. SSL Setup with Let's Encrypt

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain certificates
sudo certbot --nginx -d api.sacco.example.com
sudo certbot --nginx -d admin.sacco.example.com
sudo certbot --nginx -d portal.sacco.example.com

# Auto-renewal is configured automatically
```

#### 8. Configure Queue Worker

```bash
sudo nano /etc/systemd/system/sacco-worker.service
```

```ini
[Unit]
Description=SACCO Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/sacco/backend
ExecStart=/usr/bin/php /var/www/sacco/backend/artisan queue:work --sleep=3 --tries=3
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable sacco-worker
sudo systemctl start sacco-worker
```

#### 9. Configure Scheduled Tasks

```bash
sudo crontab -e -u www-data
```

Add:
```
* * * * * cd /var/www/sacco/backend && php artisan schedule:run >> /dev/null 2>&1
```

## Environment Configuration

### Backend (.env)

#### Production Settings

```env
APP_NAME="SACCO Management System"
APP_ENV=production
APP_KEY=base64:... # Generated by php artisan key:generate
APP_DEBUG=false
APP_URL=https://api.sacco.example.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sacco_production
DB_USERNAME=sacco_user
DB_PASSWORD=strong_password_here

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Sanctum
SANCTUM_STATEFUL_DOMAINS=admin.sacco.example.com,portal.sacco.example.com

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@sacco.example.com"
MAIL_FROM_NAME="${APP_NAME}"

# SMS
SMS_ENABLED=true
SMS_PROVIDER=africastalking
SMS_API_KEY=your_api_key
SMS_USERNAME=your_username
SMS_SENDER_ID=SACCO
```

### Frontend (.env)

#### Admin App

```env
VITE_API_URL=https://api.sacco.example.com/api
```

#### Client Portal

```env
VITE_API_URL=https://api.sacco.example.com/api
```

## Database Migration

### Backup Before Migration

```bash
# Create backup
cd backend
php artisan backup:run

# Or manual backup
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Running Migrations

```bash
# Check migration status
php artisan migrate:status

# Run pending migrations
php artisan migrate --force

# Rollback last migration (if needed)
php artisan migrate:rollback

# Rollback all and re-run
php artisan migrate:fresh --force
```

### Data Migration from Old System

If migrating from an existing system:

1. Export data from old system
2. Create migration seeders
3. Run: `php artisan db:seed --class=DataMigrationSeeder`

## Troubleshooting

### Common Issues

#### 1. Permission Errors

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### 2. Database Connection Failed

- Check database credentials in `.env`
- Verify MySQL service is running: `sudo systemctl status mysql`
- Test connection: `mysql -u username -p -h host database_name`

#### 3. 500 Internal Server Error

- Check Laravel logs: `tail -f storage/logs/laravel.log`
- Enable debug mode temporarily: `APP_DEBUG=true` in `.env`
- Check Nginx error logs: `sudo tail -f /var/log/nginx/error.log`

#### 4. CORS Errors

- Verify `SANCTUM_STATEFUL_DOMAINS` in backend `.env`
- Check frontend API URL configuration
- Ensure proper CORS headers in backend

#### 5. Queue Jobs Not Processing

```bash
# Check queue worker status
sudo systemctl status sacco-worker

# Restart queue worker
sudo systemctl restart sacco-worker

# Check failed jobs
php artisan queue:failed
```

### Performance Optimization

#### 1. Enable OPcache

```bash
sudo nano /etc/php/8.1/fpm/php.ini
```

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

#### 2. Optimize Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 3. Database Optimization

```sql
-- Add indexes for frequently queried columns
ALTER TABLE loans ADD INDEX idx_status (status);
ALTER TABLE savings_transactions ADD INDEX idx_member_date (member_id, transaction_date);
```

## Security Checklist

- [ ] Change all default passwords
- [ ] Enable SSL/HTTPS
- [ ] Set `APP_DEBUG=false` in production
- [ ] Configure firewall (UFW or iptables)
- [ ] Regular security updates
- [ ] Implement rate limiting
- [ ] Enable audit logging
- [ ] Regular database backups
- [ ] Secure file permissions
- [ ] Use strong database passwords

## Maintenance

### Regular Tasks

- **Daily**: Check system logs
- **Weekly**: Review audit logs, check disk space
- **Monthly**: Update dependencies, security patches
- **Quarterly**: Full system backup, disaster recovery test

### Backup Strategy

```bash
# Automated daily backups
php artisan backup:run

# Store backups offsite
# Configure in config/backup.php
```

## Support

For deployment assistance:
- Check documentation in `docs/` directory
- Review Laravel documentation: https://laravel.com/docs
- Contact system administrator

---

**Last Updated**: 2024
**Version**: 1.0

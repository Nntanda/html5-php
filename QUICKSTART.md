# SACCO Management System - Quick Start Guide

This guide will help you get the SACCO Management System up and running quickly.

## Prerequisites Check

Before you begin, verify you have these installed:

```bash
# Check PHP
php --version
# Should show PHP 8.1 or higher

# Check Composer
composer --version

# Check Node.js and npm
node --version
npm --version
# Should show Node 18.x or higher

# Check MySQL or PostgreSQL
mysql --version
# OR
psql --version
```

If any are missing, see [INSTALLATION.md](INSTALLATION.md) for installation instructions.

## Quick Setup (5 Minutes)

### Step 1: Setup Backend (2 minutes)

**Windows (PowerShell):**
```powershell
.\setup-backend.ps1
```

**Linux/macOS:**
```bash
chmod +x setup-backend.sh
./setup-backend.sh
```

### Step 2: Configure Database (1 minute)

1. Create database:
```sql
mysql -u root -p
CREATE DATABASE sacco_db;
EXIT;
```

2. Edit `backend/.env`:
```env
DB_DATABASE=sacco_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

3. Run migrations:
```bash
cd backend
php artisan migrate
```

### Step 3: Setup Frontend (2 minutes)

**Windows (PowerShell):**
```powershell
.\setup-frontend.ps1
```

**Linux/macOS:**
```bash
chmod +x setup-frontend.sh
./setup-frontend.sh
```

## Running the Application

Open three terminal windows:

**Terminal 1 - Backend:**
```bash
cd backend
php artisan serve
```
✅ Backend running at http://localhost:8000

**Terminal 2 - Admin App:**
```bash
cd admin-app
npm run dev
```
✅ Admin app running at http://localhost:5173

**Terminal 3 - Client Portal:**
```bash
cd client-portal
npm run dev
```
✅ Client portal running at http://localhost:5174

## First Login

### Create Super Admin User

```bash
cd backend
php artisan tinker
```

Then in tinker:
```php
$user = new App\Models\User();
$user->name = 'Super Admin';
$user->email = 'admin@sacco.local';
$user->password = Hash::make('password123');
$user->role = 'SuperAdmin';
$user->status = 'active';
$user->save();
exit
```

### Login Credentials

- **Email:** admin@sacco.local
- **Password:** password123

## What's Next?

1. **Change the default password** immediately
2. **Create additional users** (Loan Officers, Accountants)
3. **Register members** through the admin interface
4. **Configure system settings** (interest rates, loan limits)
5. **Set up email and SMS** in `backend/.env`

## Common Issues

### Port Already in Use

If port 8000 is busy:
```bash
php artisan serve --port=8001
```

Update frontend `.env` files:
```env
VITE_API_URL=http://localhost:8001/api
```

### Database Connection Failed

1. Verify MySQL/PostgreSQL is running
2. Check credentials in `backend/.env`
3. Ensure database exists

### Composer Install Fails

```bash
composer install --ignore-platform-reqs
```

### npm Install Fails

```bash
npm install --legacy-peer-deps
```

## Development Workflow

### Making Changes

1. **Backend changes:** Edit files in `backend/app/`
2. **Admin app changes:** Edit files in `admin-app/src/`
3. **Client portal changes:** Edit files in `client-portal/src/`

### Running Tests

```bash
cd backend
php artisan test
```

### Database Reset

```bash
cd backend
php artisan migrate:fresh
```

## Project Structure

```
sacco-management-system/
├── backend/              # Laravel API
│   ├── app/             # Application code
│   ├── database/        # Migrations, seeders
│   ├── routes/          # API routes
│   └── .env             # Environment config
├── admin-app/           # React Admin App
│   ├── src/             # Source code
│   └── .env             # Environment config
├── client-portal/       # React Client Portal
│   ├── src/             # Source code
│   └── .env             # Environment config
└── docs/                # Documentation
```

## Useful Commands

### Backend

```bash
# Run migrations
php artisan migrate

# Create new migration
php artisan make:migration create_table_name

# Create new controller
php artisan make:controller ControllerName

# Create new model
php artisan make:model ModelName

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Frontend

```bash
# Install new package
npm install package-name

# Build for production
npm run build

# Run linter
npm run lint
```

## Getting Help

- **API Documentation:** [docs/API_DOCUMENTATION.md](docs/API_DOCUMENTATION.md)
- **Database Schema:** [docs/DATABASE_SCHEMA.md](docs/DATABASE_SCHEMA.md)
- **Full Installation Guide:** [INSTALLATION.md](INSTALLATION.md)
- **Task List:** [.kiro/specs/sacco-management-system/tasks.md](.kiro/specs/sacco-management-system/tasks.md)

## Production Deployment

When ready for production:

1. Set `APP_ENV=production` in `backend/.env`
2. Set `APP_DEBUG=false`
3. Run `php artisan config:cache`
4. Run `php artisan route:cache`
5. Build frontend: `npm run build`
6. Configure web server (Apache/Nginx)
7. Set up SSL certificates
8. Configure automated backups

See [INSTALLATION.md](INSTALLATION.md) for detailed production deployment instructions.

## Support

For issues or questions:
1. Check the documentation in the `docs/` folder
2. Review the task list for implementation status
3. Contact the development team

---

**Happy coding! 🚀**

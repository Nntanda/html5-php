# SACCO Management System

![CI/CD Pipeline](https://github.com/YOUR_USERNAME/YOUR_REPO/workflows/CI%2FCD%20Pipeline/badge.svg)

A comprehensive SACCO (Savings and Credit Cooperative) Management System with three main components:

1. **Backend API** - Laravel (PHP) RESTful API
2. **Admin Application** - React with TypeScript for SACCO staff
3. **Client Portal** - React with TypeScript for members

## Prerequisites

Before setting up this project, ensure you have the following installed:

### Required Software

1. **PHP 8.1 or higher**
   - Download from: https://www.php.net/downloads
   - Verify installation: `php --version`

2. **Composer** (PHP dependency manager)
   - Download from: https://getcomposer.org/download/
   - Verify installation: `composer --version`

3. **Node.js 18.x or higher** (includes npm)
   - Download from: https://nodejs.org/
   - Verify installation: `node --version` and `npm --version`

4. **MySQL 8.0 or PostgreSQL 13+**
   - MySQL: https://dev.mysql.com/downloads/mysql/
   - PostgreSQL: https://www.postgresql.org/download/

5. **Git**
   - Download from: https://git-scm.com/downloads
   - Verify installation: `git --version`

## Project Structure

```
sacco-management-system/
├── backend/              # Laravel API
├── admin-app/           # React Admin Application
├── client-portal/       # React Client Portal
├── docs/                # Documentation
└── README.md
```

## Setup Instructions

### 1. Backend Setup (Laravel)

```bash
# Navigate to project root
cd backend

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env file
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=sacco_db
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations
php artisan migrate

# Start development server
php artisan serve
```

### 2. Admin Application Setup (React)

```bash
# Navigate to admin app
cd admin-app

# Install dependencies
npm install

# Copy environment file
cp .env.example .env

# Configure API URL in .env
# VITE_API_URL=http://localhost:8000/api

# Start development server
npm run dev
```

### 3. Client Portal Setup (React)

```bash
# Navigate to client portal
cd client-portal

# Install dependencies
npm install

# Copy environment file
cp .env.example .env

# Configure API URL in .env
# VITE_API_URL=http://localhost:8000/api

# Start development server
npm run dev
```

## Features

- **Authentication & Authorization**: Role-based access control (Super Admin, Loan Officer, Accountant, Member)
- **Member Management**: Registration, profile management, financial summaries
- **Savings Management**: Direct deposits, salary deduction uploads (CSV), transaction history
- **Loan Management**: Application, guarantor workflow, approval, disbursement, repayment
- **Financial Reports**: Member statements, SACCO reports, PDF/Excel exports
- **Notifications**: Email and SMS notifications for key events
- **Audit Logging**: Comprehensive activity tracking
- **Database Backup**: Automated and manual backup functionality
- **Hybrid Deployment**: Support for both online and offline deployment

## Deployment

### Quick Start (Offline/Local)

For offline or local deployment, use the automated deployment scripts:

**Linux/Mac:**
```bash
chmod +x deploy-offline.sh
./deploy-offline.sh
```

**Windows:**
```powershell
.\deploy-offline.ps1
```

After deployment, start all services:

**Linux/Mac:**
```bash
chmod +x start-services.sh
./start-services.sh
```

**Windows:**
```powershell
.\start-services.ps1
```

### Production Deployment

For production deployment to cloud servers, see:
- [Production Deployment Guide](PRODUCTION_DEPLOYMENT.md)
- [Full Deployment Documentation](docs/DEPLOYMENT.md)
- [Environment Setup Guide](docs/ENVIRONMENT_SETUP.md)
- [Production Checklist](docs/PRODUCTION_CHECKLIST.md)

## Documentation

- [API Documentation](docs/API_DOCUMENTATION.md)
- [Database Schema](docs/DATABASE_SCHEMA.md)
- [Development Guide](docs/DEVELOPMENT_GUIDE.md)
- [Deployment Guide](docs/DEPLOYMENT.md)
- [Environment Setup](docs/ENVIRONMENT_SETUP.md)
- [Production Checklist](docs/PRODUCTION_CHECKLIST.md)

## Development Status

This project is currently under development. Please refer to `.kiro/specs/sacco-management-system/tasks.md` for the implementation plan.

## Support

For issues or questions:
1. Check the documentation in the `docs/` directory
2. Review the implementation tasks in `.kiro/specs/sacco-management-system/`
3. Contact the development team

## License

Proprietary - All rights reserved

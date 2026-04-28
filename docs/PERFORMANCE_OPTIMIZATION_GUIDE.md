# SACCO Management System - Performance Optimization Guide

This guide covers performance optimization strategies and implementations for the SACCO Management System.

## Table of Contents

1. [Database Optimization](#database-optimization)
2. [API Response Caching](#api-response-caching)
3. [Frontend Optimization](#frontend-optimization)
4. [Query Optimization](#query-optimization)
5. [Performance Benchmarks](#performance-benchmarks)
6. [Monitoring & Profiling](#monitoring--profiling)

## Database Optimization

### Indexes Applied

The migration `2024_01_01_000013_add_performance_indexes_to_tables.php` adds comprehensive indexes:

#### Users Table
- `email` - For login lookups
- `role` - For role-based queries
- `status` - For active user filtering
- `(role, status)` - Composite for role + status queries

#### Members Table
- `member_number` - For member lookups
- `user_id` - For user-member relationship
- `status` - For active member filtering
- `(status, created_at)` - For recent active members

#### Savings Accounts Table
- `member_id` - For member's accounts
- `account_number` - For account lookups
- `(member_id, created_at)` - For member's recent accounts

#### Savings Transactions Table
- `account_id` - For account transactions
- `type` - For transaction type filtering
- `transaction_date` - For date-based queries
- `(account_id, transaction_date)` - For account transaction history
- `(type, transaction_date)` - For transaction reports

#### Loans Table
- `member_id` - For member's loans
- `loan_number` - For loan lookups
- `status` - For loan status filtering
- `application_date` - For application date queries
- `disbursement_date` - For disbursement tracking
- `(status, member_id)` - For member's loans by status
- `(status, disbursement_date)` - For disbursed loans reports

#### Loan Guarantors Table
- `loan_id` - For loan's guarantors
- `guarantor_member_id` - For member's guarantor requests
- `status` - For guarantor status filtering
- `(loan_id, status)` - For loan's approved guarantors

#### Loan Repayments Table
- `loan_id` - For loan's repayments
- `payment_date` - For payment date queries
- `(loan_id, payment_date)` - For loan payment history

#### Notifications Table
- `user_id` - For user's notifications
- `status` - For unread notifications
- `sent_at` - For recent notifications
- `(user_id, status)` - For user's unread notifications
- `(user_id, sent_at)` - For user's recent notifications

#### Audit Logs Table
- `user_id` - For user's actions
- `action` - For action type filtering
- `entity_type` - For entity filtering
- `created_at` - For date-based queries
- `(user_id, created_at)` - For user's recent actions
- `(entity_type, entity_id)` - For entity audit trail

#### Upload Logs Table
- `type` - For upload type filtering
- `status` - For upload status filtering
- `uploaded_by` - For user's uploads
- `created_at` - For recent uploads
- `(type, status)` - For upload reports

#### Backups Table
- `status` - For backup status filtering
- `created_at` - For recent backups

### Applying Indexes

```bash
cd backend
php artisan migrate
```

### Verifying Indexes

```sql
-- MySQL
SHOW INDEX FROM users;
SHOW INDEX FROM members;
SHOW INDEX FROM loans;

-- Check query execution plan
EXPLAIN SELECT * FROM loans WHERE status = 'approved' AND member_id = 1;
```

### Index Maintenance

```sql
-- Analyze tables (MySQL)
ANALYZE TABLE users, members, loans, savings_accounts, savings_transactions;

-- Optimize tables (MySQL)
OPTIMIZE TABLE users, members, loans;
```

## API Response Caching

### Implementing Cache for Reports

Edit `app/Http/Controllers/ReportController.php`:

```php
use Illuminate\Support\Facades\Cache;

public function savingsSummary(Request $request)
{
    $cacheKey = 'reports.savings_summary';
    $cacheDuration = 300; // 5 minutes

    return Cache::remember($cacheKey, $cacheDuration, function () {
        // Report generation logic
        $totalAccounts = SavingsAccount::count();
        $totalBalance = SavingsAccount::sum('balance');
        
        return ApiResponse::success([
            'total_accounts' => $totalAccounts,
            'total_balance' => $totalBalance,
            // ... other data
        ]);
    });
}

public function loansSummary(Request $request)
{
    $cacheKey = 'reports.loans_summary';
    $cacheDuration = 300;

    return Cache::remember($cacheKey, $cacheDuration, function () {
        // Report generation logic
        return ApiResponse::success([
            'total_loans' => Loan::count(),
            'total_disbursed' => Loan::where('status', 'disbursed')->sum('amount'),
            // ... other data
        ]);
    });
}
```

### Cache Invalidation

Invalidate cache when data changes:

```php
// In LoanController after disbursement
Cache::forget('reports.loans_summary');

// In SavingsTransactionController after deposit
Cache::forget('reports.savings_summary');
```

### Cache Configuration

Edit `config/cache.php`:

```php
'default' => env('CACHE_DRIVER', 'redis'), // Use Redis for better performance

'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],
```

### Cache Commands

```bash
# Clear all cache
php artisan cache:clear

# Clear specific cache
php artisan cache:forget reports.savings_summary

# View cache statistics (if using Redis)
redis-cli INFO stats
```

## Frontend Optimization

### Admin Application

#### Code Splitting

Edit `admin-app/vite.config.ts`:

```typescript
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          'react-vendor': ['react', 'react-dom', 'react-router-dom'],
          'ui-vendor': ['zustand', 'axios'],
        },
      },
    },
    chunkSizeWarningLimit: 1000,
  },
});
```

#### Lazy Loading Routes

Edit `admin-app/src/router/index.tsx`:

```typescript
import { lazy, Suspense } from 'react';

// Lazy load pages
const Dashboard = lazy(() => import('../pages/Dashboard'));
const Members = lazy(() => import('../pages/Members'));
const Loans = lazy(() => import('../pages/Loans'));
const Reports = lazy(() => import('../pages/Reports'));

// Wrap routes with Suspense
<Route
  path="/dashboard"
  element={
    <Suspense fallback={<div>Loading...</div>}>
      <Dashboard />
    </Suspense>
  }
/>
```

#### Image Optimization

```typescript
// Use optimized images
<img 
  src="/images/logo.png" 
  alt="Logo"
  loading="lazy"
  width="200"
  height="50"
/>
```

#### Bundle Analysis

```bash
cd admin-app

# Install bundle analyzer
npm install --save-dev rollup-plugin-visualizer

# Build and analyze
npm run build

# Check dist folder for bundle sizes
ls -lh dist/assets/
```

### Client Portal

Apply same optimizations as Admin Application.

### Performance Targets

- **Initial Load**: < 3 seconds
- **Time to Interactive**: < 5 seconds
- **Main Bundle**: < 500KB (gzipped)
- **Vendor Bundle**: < 1MB (gzipped)
- **Lazy Loaded Chunks**: < 200KB each

### Optimization Checklist

- [x] Code splitting implemented
- [x] Lazy loading for routes
- [x] Tree shaking enabled
- [x] Minification enabled
- [x] Compression (gzip) enabled
- [ ] Image optimization
- [ ] Font optimization
- [ ] Service worker for caching
- [ ] CDN for static assets

## Query Optimization

### Eager Loading

Prevent N+1 queries by eager loading relationships:

```php
// Bad: N+1 query problem
$members = Member::all();
foreach ($members as $member) {
    echo $member->user->name; // Queries user for each member
}

// Good: Eager loading
$members = Member::with('user')->get();
foreach ($members as $member) {
    echo $member->user->name; // No additional queries
}
```

#### Implementation Examples

**MemberController:**
```php
public function index(Request $request)
{
    $members = Member::with(['user', 'savingsAccount'])
        ->when($request->search, function ($query, $search) {
            $query->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('member_number', 'like', "%{$search}%");
        })
        ->paginate(20);

    return ApiResponse::success($members);
}
```

**LoanController:**
```php
public function index(Request $request)
{
    $loans = Loan::with(['member.user', 'guarantors.guarantorMember'])
        ->when($request->status, function ($query, $status) {
            $query->where('status', $status);
        })
        ->orderBy('created_at', 'desc')
        ->paginate(20);

    return ApiResponse::success($loans);
}
```

### Query Optimization Tips

1. **Use Select to Limit Columns**
   ```php
   Member::select('id', 'first_name', 'last_name', 'member_number')->get();
   ```

2. **Use Chunking for Large Datasets**
   ```php
   Member::chunk(100, function ($members) {
       foreach ($members as $member) {
           // Process member
       }
   });
   ```

3. **Use Exists Instead of Count**
   ```php
   // Bad
   if (Loan::where('member_id', $id)->count() > 0) { }

   // Good
   if (Loan::where('member_id', $id)->exists()) { }
   ```

4. **Avoid SELECT ***
   ```php
   // Bad
   $members = DB::table('members')->get();

   // Good
   $members = DB::table('members')
       ->select('id', 'first_name', 'last_name')
       ->get();
   ```

5. **Use Pagination**
   ```php
   // Always paginate large result sets
   $members = Member::paginate(20);
   ```

### Query Monitoring

Enable query logging temporarily:

```php
// In a controller method
DB::enableQueryLog();

// Your queries here
$members = Member::with('user')->get();

// Get executed queries
$queries = DB::getQueryLog();
dd($queries);

DB::disableQueryLog();
```

## Performance Benchmarks

### Expected Performance Metrics

#### API Response Times
- **Authentication**: < 200ms
- **Member List**: < 300ms
- **Loan List**: < 300ms
- **Transaction History**: < 250ms
- **Simple Reports**: < 500ms
- **Complex Reports**: < 3 seconds
- **CSV Upload (100 records)**: < 10 seconds

#### Database Query Times
- **Simple SELECT**: < 10ms
- **JOIN Query**: < 50ms
- **Aggregation Query**: < 100ms
- **Full-text Search**: < 200ms

#### Frontend Performance
- **First Contentful Paint**: < 1.5s
- **Time to Interactive**: < 3.5s
- **Largest Contentful Paint**: < 2.5s
- **Cumulative Layout Shift**: < 0.1

### Running Performance Tests

```bash
cd backend

# Run performance test suite
php artisan test --filter=PerformanceTest

# Run specific performance tests
php artisan test --filter=test_member_search_performance_with_indexes
php artisan test --filter=test_bulk_transaction_processing_performance
php artisan test --filter=test_complex_report_generation_performance
```

### Load Testing

Use Apache Bench or similar tools:

```bash
# Test member listing endpoint
ab -n 1000 -c 10 -H "Authorization: Bearer YOUR_TOKEN" \
   http://localhost:8000/api/members

# Test loan listing endpoint
ab -n 500 -c 5 -H "Authorization: Bearer YOUR_TOKEN" \
   http://localhost:8000/api/loans
```

## Monitoring & Profiling

### Laravel Telescope (Development)

Install Telescope for development monitoring:

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Access at: `http://localhost:8000/telescope`

### Query Profiling

Use Laravel Debugbar:

```bash
composer require barryvdh/laravel-debugbar --dev
```

### Production Monitoring

#### Application Performance Monitoring (APM)

Consider using:
- New Relic
- Datadog
- Scout APM
- Blackfire.io

#### Log Monitoring

```bash
# Monitor Laravel logs
tail -f storage/logs/laravel.log

# Monitor slow queries (MySQL)
# Enable slow query log in my.cnf:
# slow_query_log = 1
# slow_query_log_file = /var/log/mysql/slow-query.log
# long_query_time = 2
```

#### Server Monitoring

Monitor:
- CPU usage
- Memory usage
- Disk I/O
- Network traffic
- Database connections
- Queue workers

### Performance Optimization Commands

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize

# Clear all caches
php artisan optimize:clear
```

## Database Connection Pooling

### Configure Connection Pool

Edit `config/database.php`:

```php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => null,
    'options' => [
        PDO::ATTR_PERSISTENT => true, // Enable persistent connections
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
],
```

### MySQL Configuration

Edit `my.cnf`:

```ini
[mysqld]
max_connections = 200
thread_cache_size = 16
query_cache_size = 64M
query_cache_limit = 2M
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
```

## Redis Configuration

### Install Redis

```bash
# Ubuntu/Debian
sudo apt install redis-server

# Start Redis
sudo systemctl start redis
sudo systemctl enable redis
```

### Configure Laravel to Use Redis

Edit `.env`:

```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Redis Performance Tips

```bash
# Monitor Redis
redis-cli MONITOR

# Check Redis stats
redis-cli INFO stats

# Clear Redis cache
redis-cli FLUSHALL
```

## Queue Optimization

### Configure Queue Workers

```bash
# Start queue worker with optimal settings
php artisan queue:work --sleep=3 --tries=3 --max-jobs=1000 --max-time=3600
```

### Supervisor Configuration

Create `/etc/supervisor/conf.d/sacco-worker.conf`:

```ini
[program:sacco-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sacco/backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/sacco/backend/storage/logs/worker.log
stopwaitsecs=3600
```

## Performance Checklist

### Database
- [x] Indexes added to frequently queried columns
- [x] Composite indexes for multi-column queries
- [x] Foreign key indexes
- [x] Query optimization with EXPLAIN
- [ ] Database connection pooling configured
- [ ] Query caching enabled

### API
- [ ] Response caching implemented
- [x] Pagination for large datasets
- [x] Eager loading to prevent N+1 queries
- [x] API rate limiting
- [ ] Response compression (gzip)
- [x] Efficient serialization

### Frontend
- [x] Code splitting
- [x] Lazy loading
- [x] Tree shaking
- [x] Minification
- [ ] Image optimization
- [ ] CDN for static assets
- [ ] Service worker caching

### Server
- [ ] OPcache enabled
- [ ] Redis for caching
- [ ] Queue workers running
- [ ] Supervisor for process management
- [ ] Load balancing (if needed)
- [ ] CDN for static assets

## Troubleshooting Performance Issues

### Slow Queries

1. Enable query logging
2. Identify slow queries
3. Use EXPLAIN to analyze
4. Add appropriate indexes
5. Optimize query structure

### High Memory Usage

1. Check for memory leaks
2. Optimize large data exports
3. Use chunking for bulk operations
4. Increase PHP memory limit if needed
5. Monitor with profiling tools

### Slow API Responses

1. Check database query performance
2. Verify indexes are used
3. Implement caching
4. Optimize eager loading
5. Reduce payload size

---

**Last Updated**: 2024
**Version**: 1.0

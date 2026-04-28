# SACCO Management System - Development Guide

## Table of Contents

1. [Development Environment Setup](#development-environment-setup)
2. [Project Architecture](#project-architecture)
3. [Coding Standards](#coding-standards)
4. [Git Workflow](#git-workflow)
5. [Testing Guidelines](#testing-guidelines)
6. [API Development](#api-development)
7. [Frontend Development](#frontend-development)
8. [Database Management](#database-management)
9. [Debugging](#debugging)
10. [Performance Optimization](#performance-optimization)

## Development Environment Setup

### IDE Recommendations

**Visual Studio Code** (Recommended)
- Extensions:
  - PHP Intelephense
  - Laravel Extension Pack
  - ESLint
  - Prettier
  - Tailwind CSS IntelliSense
  - GitLens

**PHPStorm** (Alternative)
- Built-in Laravel support
- Excellent PHP debugging
- Database tools included

### Environment Configuration

#### Backend (.env)
```env
APP_ENV=local
APP_DEBUG=true
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sacco_db
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=sync  # Use 'database' for production
```

#### Frontend (.env)
```env
VITE_API_URL=http://localhost:8000/api
VITE_APP_NAME=SACCO Admin Portal
VITE_DEBUG=true
```

## Project Architecture

### Backend Architecture (Laravel)

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # API controllers
│   │   ├── Middleware/      # Custom middleware
│   │   └── Resources/       # API resources (transformers)
│   ├── Models/              # Eloquent models
│   ├── Services/            # Business logic services
│   ├── Policies/            # Authorization policies
│   └── Events/              # Event classes
├── database/
│   ├── migrations/          # Database migrations
│   ├── seeders/             # Database seeders
│   └── factories/           # Model factories
├── routes/
│   └── api.php              # API routes
├── tests/
│   ├── Feature/             # Feature tests
│   └── Unit/                # Unit tests
└── config/                  # Configuration files
```

### Frontend Architecture (React)

```
admin-app/ or client-portal/
├── src/
│   ├── components/          # Reusable components
│   │   ├── common/          # Common UI components
│   │   ├── forms/           # Form components
│   │   └── layout/          # Layout components
│   ├── pages/               # Page components
│   ├── hooks/               # Custom React hooks
│   ├── services/            # API service layer
│   ├── store/               # State management (Zustand)
│   ├── types/               # TypeScript type definitions
│   ├── utils/               # Utility functions
│   └── App.tsx              # Root component
├── public/                  # Static assets
└── tests/                   # Component tests
```

## Coding Standards

### PHP (Backend)

Follow PSR-12 coding standards:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MemberController extends Controller
{
    /**
     * Display a listing of members.
     */
    public function index(Request $request): JsonResponse
    {
        $members = Member::query()
            ->when($request->search, function ($query, $search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            })
            ->paginate(15);

        return response()->json($members);
    }
}
```

**Key Points:**
- Use type hints for parameters and return types
- Use meaningful variable names
- Add PHPDoc comments for complex methods
- Keep methods focused and small
- Use dependency injection

### TypeScript (Frontend)

```typescript
// types/member.ts
export interface Member {
  id: number;
  memberNumber: string;
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  status: 'active' | 'inactive' | 'suspended';
}

// services/memberService.ts
import axios from 'axios';
import { Member } from '../types/member';

export const memberService = {
  async getMembers(params?: {
    page?: number;
    search?: string;
  }): Promise<{ data: Member[]; meta: any }> {
    const response = await axios.get('/members', { params });
    return response.data;
  },

  async getMember(id: number): Promise<Member> {
    const response = await axios.get(`/members/${id}`);
    return response.data;
  },
};

// components/MemberList.tsx
import React, { useEffect, useState } from 'react';
import { memberService } from '../services/memberService';
import { Member } from '../types/member';

export const MemberList: React.FC = () => {
  const [members, setMembers] = useState<Member[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadMembers();
  }, []);

  const loadMembers = async () => {
    try {
      const { data } = await memberService.getMembers();
      setMembers(data);
    } catch (error) {
      console.error('Failed to load members:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div>Loading...</div>;

  return (
    <div>
      {members.map(member => (
        <div key={member.id}>{member.firstName} {member.lastName}</div>
      ))}
    </div>
  );
};
```

**Key Points:**
- Always use TypeScript, never `any` type unless absolutely necessary
- Define interfaces for all data structures
- Use functional components with hooks
- Separate business logic from UI components
- Use proper error handling

## Git Workflow

### Branch Naming

- `main` - Production-ready code
- `develop` - Development branch
- `feature/task-1-project-setup` - Feature branches
- `bugfix/fix-login-error` - Bug fix branches
- `hotfix/critical-security-fix` - Hotfix branches

### Commit Messages

Follow Conventional Commits:

```
feat: add member registration endpoint
fix: resolve database connection timeout
docs: update API documentation
test: add unit tests for loan service
refactor: simplify savings calculation logic
chore: update dependencies
```

### Pull Request Process

1. Create feature branch from `develop`
2. Implement feature with tests
3. Ensure all tests pass
4. Create pull request to `develop`
5. Code review by team member
6. Merge after approval

## Testing Guidelines

### Backend Testing (PHPUnit)

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_member(): void
    {
        $user = User::factory()->create(['role' => 'SuperAdmin']);
        
        $response = $this->actingAs($user)
            ->postJson('/api/members', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'phone' => '+256700000000',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'member_number',
                'first_name',
                'last_name',
            ]);

        $this->assertDatabaseHas('members', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    public function test_cannot_create_member_without_authentication(): void
    {
        $response = $this->postJson('/api/members', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response->assertStatus(401);
    }
}
```

**Run Tests:**
```bash
cd backend
php artisan test
php artisan test --filter MemberTest
```

### Frontend Testing (Vitest + React Testing Library)

```typescript
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemberList } from './MemberList';
import { memberService } from '../services/memberService';

vi.mock('../services/memberService');

describe('MemberList', () => {
  it('displays members after loading', async () => {
    const mockMembers = [
      { id: 1, firstName: 'John', lastName: 'Doe', memberNumber: 'MEM001' },
    ];

    vi.mocked(memberService.getMembers).mockResolvedValue({
      data: mockMembers,
      meta: {},
    });

    render(<MemberList />);

    await waitFor(() => {
      expect(screen.getByText('John Doe')).toBeInTheDocument();
    });
  });
});
```

## API Development

### Creating a New Endpoint

1. **Create Migration:**
```bash
php artisan make:migration create_table_name
```

2. **Create Model:**
```bash
php artisan make:model ModelName
```

3. **Create Controller:**
```bash
php artisan make:controller ModelNameController --api
```

4. **Create Resource:**
```bash
php artisan make:resource ModelNameResource
```

5. **Add Routes:**
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('models', ModelNameController::class);
});
```

6. **Implement Controller:**
```php
public function index(Request $request)
{
    $models = ModelName::paginate(15);
    return ModelNameResource::collection($models);
}
```

7. **Write Tests:**
```php
public function test_can_list_models()
{
    // Test implementation
}
```

## Frontend Development

### Creating a New Page

1. **Create Page Component:**
```typescript
// src/pages/MemberListPage.tsx
export const MemberListPage: React.FC = () => {
  return <div>Member List</div>;
};
```

2. **Add Route:**
```typescript
// src/App.tsx
<Route path="/members" element={<MemberListPage />} />
```

3. **Create Service:**
```typescript
// src/services/memberService.ts
export const memberService = {
  async getMembers() {
    // Implementation
  },
};
```

4. **Add Types:**
```typescript
// src/types/member.ts
export interface Member {
  // Type definition
}
```

## Database Management

### Creating Migrations

```bash
php artisan make:migration create_members_table
```

```php
public function up()
{
    Schema::create('members', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('member_number')->unique();
        $table->string('first_name');
        $table->string('last_name');
        $table->timestamps();
        
        $table->index('member_number');
    });
}
```

### Running Migrations

```bash
php artisan migrate              # Run migrations
php artisan migrate:rollback     # Rollback last batch
php artisan migrate:fresh        # Drop all tables and re-run
php artisan migrate:fresh --seed # With seeders
```

### Creating Seeders

```bash
php artisan make:seeder MemberSeeder
```

```php
public function run()
{
    Member::factory()->count(50)->create();
}
```

## Debugging

### Backend Debugging

```php
// Use Laravel's dd() helper
dd($variable);

// Use Log facade
Log::info('Debug message', ['data' => $data]);

// Use Ray (if installed)
ray($variable);
```

### Frontend Debugging

```typescript
// Console logging
console.log('Debug:', data);

// React DevTools
// Install React DevTools browser extension

// Network debugging
// Use browser DevTools Network tab
```

## Performance Optimization

### Backend

1. **Database Queries:**
```php
// Use eager loading
$members = Member::with('user', 'savingsAccounts')->get();

// Use select to limit columns
$members = Member::select('id', 'first_name', 'last_name')->get();

// Use indexes
Schema::table('members', function (Blueprint $table) {
    $table->index('member_number');
});
```

2. **Caching:**
```php
$members = Cache::remember('members', 3600, function () {
    return Member::all();
});
```

3. **Queue Jobs:**
```php
SendNotificationJob::dispatch($user, $message);
```

### Frontend

1. **Code Splitting:**
```typescript
const MemberList = lazy(() => import('./pages/MemberListPage'));
```

2. **Memoization:**
```typescript
const memoizedValue = useMemo(() => computeExpensiveValue(a, b), [a, b]);
```

3. **Debouncing:**
```typescript
const debouncedSearch = useMemo(
  () => debounce((value) => search(value), 300),
  []
);
```

## Common Tasks

### Add New User Role

1. Update role enum in User model
2. Update middleware
3. Update policies
4. Update frontend role checks

### Add New API Endpoint

1. Create/update controller method
2. Add route
3. Create resource if needed
4. Write tests
5. Update API documentation

### Add New Frontend Feature

1. Create types
2. Create service methods
3. Create components
4. Add routes
5. Write tests

## Resources

- Laravel: https://laravel.com/docs
- React: https://react.dev
- TypeScript: https://www.typescriptlang.org/docs
- Tailwind CSS: https://tailwindcss.com/docs
- Vitest: https://vitest.dev
- React Testing Library: https://testing-library.com/react

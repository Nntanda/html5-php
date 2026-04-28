# SACCO Admin Application - Quick Start Guide

## Overview
The admin application is a React + TypeScript web interface for SACCO staff to manage members, loans, savings, and system operations.

## Quick Setup

### 1. Install Dependencies
```bash
cd admin-app
npm install
```

### 2. Configure Environment
```bash
cp .env.example .env
```

Update `.env` if your API is not at `http://localhost:8000/api`:
```
VITE_API_URL=http://localhost:8000/api
```

### 3. Start Development Server
```bash
npm run dev
```

The app will be available at `http://localhost:5173`

## Login

Use credentials from your backend:
- Email: admin@sacco.com
- Password: password

## User Roles

### SuperAdmin
- Full access to all features
- Can manage users, configuration, audit logs, backups

### LoanOfficer
- Can manage loan applications and approvals
- Can view member data and reports

### Accountant
- Can process loan disbursements
- Can manage savings and transactions
- Can view financial reports

## Project Structure

```
admin-app/
├── src/
│   ├── api/              # API client and endpoints
│   ├── components/       # Reusable components
│   ├── pages/           # Page components
│   ├── router/          # Route configuration
│   ├── store/           # Zustand state management
│   ├── types/           # TypeScript types
│   ├── App.tsx          # Main app
│   ├── main.tsx         # Entry point
│   └── index.css        # Global styles
├── package.json         # Dependencies
├── tsconfig.json        # TypeScript config
├── vite.config.ts       # Vite config
└── tailwind.config.js   # Tailwind config
```

## Key Technologies

- **React 18** - UI framework
- **TypeScript** - Type safety
- **Vite** - Build tool
- **React Router v6** - Navigation
- **Zustand** - State management
- **Axios** - HTTP client
- **React Hook Form** - Form handling
- **Zod** - Schema validation
- **Tailwind CSS** - Styling

## Common Tasks

### Add a New Page

1. Create page component in `src/pages/`:
```typescript
export const NewPage: React.FC = () => {
  return (
    <div>
      <h1>New Page</h1>
    </div>
  );
};
```

2. Add route in `src/router/index.tsx`:
```typescript
{
  path: 'new-page',
  element: <ProtectedRoute><NewPage /></ProtectedRoute>,
}
```

3. Add menu item in `src/components/Sidebar.tsx`:
```typescript
{
  id: 'new-page',
  label: 'New Page',
  path: '/new-page',
  icon: '📄',
  roles: ['SuperAdmin', 'LoanOfficer'],
}
```

### Add API Endpoint

1. Add method in `src/api/auth.ts` or create new file:
```typescript
export const membersApi = {
  getMembers: async (): Promise<Member[]> => {
    const response = await apiClient.get<Member[]>('/members');
    return response.data;
  },
};
```

2. Use in component:
```typescript
import { membersApi } from '../api/members';

const [members, setMembers] = useState<Member[]>([]);

useEffect(() => {
  membersApi.getMembers().then(setMembers);
}, []);
```

### Add Form with Validation

```typescript
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

const schema = z.object({
  email: z.string().email(),
  name: z.string().min(1),
});

type FormData = z.infer<typeof schema>;

export const MyForm: React.FC = () => {
  const { register, handleSubmit, formState: { errors } } = useForm<FormData>({
    resolver: zodResolver(schema),
  });

  const onSubmit = (data: FormData) => {
    console.log(data);
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <input {...register('email')} />
      {errors.email && <p>{errors.email.message}</p>}
      <button type="submit">Submit</button>
    </form>
  );
};
```

## Authentication

The app automatically:
- Stores JWT token in localStorage
- Includes token in all API requests
- Redirects to login on 401 errors
- Supports token refresh

## Styling

Use Tailwind CSS classes:
```typescript
<div className="bg-white rounded-lg shadow p-6">
  <h1 className="text-2xl font-bold text-gray-800">Title</h1>
  <p className="text-gray-600">Description</p>
</div>
```

## Development Commands

```bash
# Start dev server
npm run dev

# Build for production
npm run build

# Check code quality
npm run lint

# Preview production build
npm run preview
```

## Troubleshooting

### API Connection Issues
- Ensure backend is running at `http://localhost:8000`
- Check `.env` file has correct API URL
- Check browser console for CORS errors

### Login Issues
- Verify credentials are correct
- Check backend is running
- Check network tab in browser dev tools

### Build Errors
- Delete `node_modules` and `dist` folders
- Run `npm install` again
- Check TypeScript errors with `npm run lint`

## Next Steps

1. Implement member management UI (Task 14)
2. Implement savings management UI (Task 15)
3. Implement loan management UI (Task 16)
4. Implement repayment management UI (Task 17)
5. Implement reports and notifications UI (Task 18)
6. Implement system administration UI (Task 19)

## Resources

- [React Documentation](https://react.dev)
- [TypeScript Documentation](https://www.typescriptlang.org)
- [React Router Documentation](https://reactrouter.com)
- [Tailwind CSS Documentation](https://tailwindcss.com)
- [Zustand Documentation](https://github.com/pmndrs/zustand)
- [React Hook Form Documentation](https://react-hook-form.com)
- [Zod Documentation](https://zod.dev)

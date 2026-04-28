# Client Portal Quick Start Guide

## Overview
The SACCO Client Portal is a React TypeScript application that allows members to manage their savings, loans, and account information.

## Getting Started

### Prerequisites
- Node.js 16+ and npm
- Backend API running at `http://localhost:8000/api`

### Installation

1. **Navigate to client-portal directory:**
   ```bash
   cd client-portal
   ```

2. **Install dependencies:**
   ```bash
   npm install
   ```

3. **Create environment file:**
   ```bash
   cp .env.example .env
   ```

4. **Update .env if needed:**
   ```
   VITE_API_URL=http://localhost:8000/api
   ```

### Development

**Start the development server:**
```bash
npm run dev
```

The application will open at `http://localhost:5173`

### Build for Production

```bash
npm run build
```

Output will be in the `dist/` directory.

## Project Structure

```
src/
├── api/              # API client and endpoints
├── components/       # Reusable React components
├── pages/           # Page components for routes
├── router/          # React Router configuration
├── store/           # Zustand state management
├── types/           # TypeScript type definitions
├── App.tsx          # Main app component
├── index.css        # Tailwind CSS imports
└── main.tsx         # React entry point
```

## Key Features

### Authentication
- Login with email and password
- JWT token-based authentication
- Automatic token refresh
- Secure logout

### Navigation
- Sidebar menu with role-based items
- Active route highlighting
- Quick access to all features

### Pages
- **Dashboard:** Account overview and quick actions
- **Profile:** User information and password change
- **Savings:** Savings account details
- **Loans:** Loan applications and history
- **Transactions:** Transaction history with filtering
- **Reports:** Account statements and downloads

## Development Workflow

### Adding a New Page

1. **Create page component in `src/pages/`:**
   ```typescript
   export const NewPage: React.FC = () => {
     return <div>New Page Content</div>;
   };
   ```

2. **Add route in `src/router/index.tsx`:**
   ```typescript
   {
     path: 'new-page',
     element: <NewPage />,
   }
   ```

3. **Add menu item in `src/components/Navigation.tsx`:**
   ```typescript
   {
     id: 'new-page',
     label: 'New Page',
     path: '/new-page',
     icon: '📄',
     roles: ['Member'],
   }
   ```

### Using the Auth Store

```typescript
import { useAuthStore } from '../store/authStore';

export const MyComponent: React.FC = () => {
  const { user, login, logout, isAuthenticated } = useAuthStore();
  
  return (
    <div>
      {isAuthenticated && <p>Welcome, {user?.name}</p>}
    </div>
  );
};
```

### Making API Calls

```typescript
import { apiClient } from '../api/client';

// GET request
const response = await apiClient.get('/endpoint');

// POST request
const response = await apiClient.post('/endpoint', { data });

// PUT request
const response = await apiClient.put('/endpoint', { data });

// DELETE request
const response = await apiClient.delete('/endpoint');
```

### Form Validation

```typescript
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

const schema = z.object({
  email: z.string().email('Invalid email'),
  password: z.string().min(6, 'Minimum 6 characters'),
});

type FormData = z.infer<typeof schema>;

export const MyForm: React.FC = () => {
  const { register, handleSubmit, formState: { errors } } = useForm<FormData>({
    resolver: zodResolver(schema),
  });

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <input {...register('email')} />
      {errors.email && <p>{errors.email.message}</p>}
    </form>
  );
};
```

## Styling

The project uses Tailwind CSS for styling. All components use Tailwind utility classes.

### Common Tailwind Classes
- `bg-green-600` - Green background (primary color)
- `text-gray-800` - Dark gray text
- `rounded-lg` - Rounded corners
- `shadow` - Box shadow
- `hover:bg-green-700` - Hover state
- `transition-colors` - Smooth color transition

## Testing

### Run Linter
```bash
npm run lint
```

### Build Check
```bash
npm run build
```

## Troubleshooting

### Port Already in Use
If port 5173 is already in use, Vite will use the next available port.

### API Connection Issues
- Ensure backend is running at `http://localhost:8000`
- Check `VITE_API_URL` in `.env`
- Check browser console for CORS errors

### Login Issues
- Verify backend authentication endpoint is working
- Check demo credentials: `member@sacco.com` / `password`
- Check browser localStorage for auth token

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `VITE_API_URL` | `http://localhost:8000/api` | Backend API base URL |

## Dependencies

- **react** - UI library
- **react-router-dom** - Routing
- **zustand** - State management
- **axios** - HTTP client
- **react-hook-form** - Form handling
- **zod** - Schema validation
- **tailwindcss** - CSS framework

## Resources

- [React Documentation](https://react.dev)
- [React Router Documentation](https://reactrouter.com)
- [Zustand Documentation](https://github.com/pmndrs/zustand)
- [Tailwind CSS Documentation](https://tailwindcss.com)
- [TypeScript Documentation](https://www.typescriptlang.org)

## Support

For issues or questions, refer to the main project documentation or contact the development team.

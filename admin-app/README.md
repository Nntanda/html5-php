# SACCO Admin Application

A React + TypeScript admin dashboard for the SACCO Management System.

## Features

- **Authentication**: JWT-based login with token refresh
- **Role-Based Access Control**: Different menu items and routes based on user role
- **Responsive Design**: Mobile-friendly layout using Tailwind CSS
- **Type Safety**: Full TypeScript support
- **State Management**: Zustand for global state
- **Form Validation**: React Hook Form with Zod validation
- **API Integration**: Axios client with interceptors

## Setup

### Prerequisites

- Node.js 16+ and npm

### Installation

1. Install dependencies:
```bash
npm install
```

2. Create `.env` file from `.env.example`:
```bash
cp .env.example .env
```

3. Update `.env` with your API URL if needed:
```
VITE_API_URL=http://localhost:8000/api
```

### Development

Start the development server:
```bash
npm run dev
```

The app will be available at `http://localhost:5173`

### Build

Build for production:
```bash
npm run build
```

### Linting

Check code quality:
```bash
npm run lint
```

## Project Structure

```
src/
├── api/              # API client and endpoints
├── components/       # Reusable React components
├── pages/           # Page components
├── router/          # React Router configuration
├── store/           # Zustand stores
├── types/           # TypeScript type definitions
├── App.tsx          # Main app component
├── main.tsx         # Entry point
└── index.css        # Global styles
```

## Authentication

The app uses JWT tokens stored in localStorage. The API client automatically:
- Includes the token in request headers
- Handles 401 responses by redirecting to login
- Supports token refresh

## User Roles

- **SuperAdmin**: Full access to all features
- **LoanOfficer**: Access to loan management and member data
- **Accountant**: Access to financial operations and reports
- **Member**: Limited access (for future client portal)

## API Integration

The API client is configured to:
- Use the backend API at `http://localhost:8000/api`
- Automatically include authentication tokens
- Handle errors gracefully
- Support all HTTP methods (GET, POST, PUT, DELETE)

## Development Notes

- Use TypeScript for all new code
- Follow the existing component structure
- Add types for all API responses
- Use Tailwind CSS for styling
- Keep components small and focused

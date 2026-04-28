import { createBrowserRouter } from 'react-router-dom';
import { Login } from '../pages/Login';
import { ForgotPassword } from '../pages/ForgotPassword';
import { ResetPassword } from '../pages/ResetPassword';
import { Dashboard } from '../pages/Dashboard';
import { Unauthorized } from '../pages/Unauthorized';
import { Members } from '../pages/Members';
import { Users } from '../pages/Users';
import { Savings } from '../pages/Savings';
import { CreateSavingsAccount } from '../pages/CreateSavingsAccount';
import { SalaryDeductionPreview } from '../pages/SalaryDeductionPreview';
import { SalaryDeductionHistory } from '../pages/SalaryDeductionHistory';
import { Loans } from '../pages/Loans';
import { Repayments } from '../pages/Repayments';
import { Reports } from '../pages/Reports';
import { Notifications } from '../pages/Notifications';
import { Config } from '../pages/Config';
import { Audit } from '../pages/Audit';
import { Backups } from '../pages/Backups';
import { Layout } from '../components/Layout';
import { ProtectedRoute } from '../components/ProtectedRoute';

export const router = createBrowserRouter([
  {
    path: '/login',
    element: <Login />,
  },
  {
    path: '/forgot-password',
    element: <ForgotPassword />,
  },
  {
    path: '/reset-password',
    element: <ResetPassword />,
  },
  {
    path: '/unauthorized',
    element: <Unauthorized />,
  },
  {
    path: '/',
    element: (
      <ProtectedRoute>
        <Layout />
      </ProtectedRoute>
    ),
    children: [
      {
        path: 'dashboard',
        element: <Dashboard />,
      },
      {
        path: 'members',
        element: <ProtectedRoute><Members /></ProtectedRoute>,
      },
      {
        path: 'users',
        element: <ProtectedRoute requiredRoles={['SuperAdmin']}><Users /></ProtectedRoute>,
      },
      {
        path: 'savings',
        element: <ProtectedRoute><Savings /></ProtectedRoute>,
      },
      {
        path: 'savings/create-account',
        element: <ProtectedRoute><CreateSavingsAccount /></ProtectedRoute>,
      },
      {
        path: 'savings/deduction-preview',
        element: <ProtectedRoute><SalaryDeductionPreview /></ProtectedRoute>,
      },
      {
        path: 'savings/deduction-history',
        element: <ProtectedRoute><SalaryDeductionHistory /></ProtectedRoute>,
      },
      {
        path: 'loans',
        element: <ProtectedRoute><Loans /></ProtectedRoute>,
      },
      {
        path: 'repayments',
        element: <ProtectedRoute><Repayments /></ProtectedRoute>,
      },
      {
        path: 'reports',
        element: <ProtectedRoute><Reports /></ProtectedRoute>,
      },
      {
        path: 'notifications',
        element: <ProtectedRoute><Notifications /></ProtectedRoute>,
      },
      {
        path: 'config',
        element: <ProtectedRoute requiredRoles={['SuperAdmin']}><Config /></ProtectedRoute>,
      },
      {
        path: 'audit',
        element: <ProtectedRoute requiredRoles={['SuperAdmin']}><Audit /></ProtectedRoute>,
      },
      {
        path: 'backups',
        element: <ProtectedRoute requiredRoles={['SuperAdmin']}><Backups /></ProtectedRoute>,
      },
      {
        path: '',
        element: <Dashboard />,
      },
    ],
  },
], {
  future: {
    v7_startTransition: true,
  },
});

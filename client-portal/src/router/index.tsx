import { createBrowserRouter } from 'react-router-dom';
import { Login } from '../pages/Login';
import { Register } from '../pages/Register';
import { ForgotPassword } from '../pages/ForgotPassword';
import { ResetPassword } from '../pages/ResetPassword';
import { Dashboard } from '../pages/Dashboard';
import { Profile } from '../pages/Profile';
import { Savings } from '../pages/Savings';
import { Loans } from '../pages/Loans';
import { LoanApplication } from '../pages/LoanApplication';
import { Transactions } from '../pages/Transactions';
import { Statements } from '../pages/Statements';
import { Reports } from '../pages/Reports';
import { GuarantorRequests } from '../pages/GuarantorRequests';
import { Notifications } from '../pages/Notifications';
import { Support } from '../pages/Support';
import { Unauthorized } from '../pages/Unauthorized';
import { Layout } from '../components/Layout';
import { ProtectedRoute } from '../components/ProtectedRoute';

export const router = createBrowserRouter([
  {
    path: '/login',
    element: <Login />,
  },
  {
    path: '/register',
    element: <Register />,
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
        path: 'profile',
        element: <Profile />,
      },
      {
        path: 'savings',
        element: <Savings />,
      },
      {
        path: 'loans',
        element: <Loans />,
      },
      {
        path: 'loans/apply',
        element: <LoanApplication />,
      },
      {
        path: 'transactions',
        element: <Transactions />,
      },
      {
        path: 'statements',
        element: <Statements />,
      },
      {
        path: 'reports',
        element: <Reports />,
      },
      {
        path: 'guarantor-requests',
        element: <GuarantorRequests />,
      },
      {
        path: 'notifications',
        element: <Notifications />,
      },
      {
        path: 'support',
        element: <Support />,
      },
      {
        path: '',
        element: <Dashboard />,
      },
    ],
  },
]);

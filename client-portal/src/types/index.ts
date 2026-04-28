// User roles
export type UserRole = 'SuperAdmin' | 'LoanOfficer' | 'Accountant' | 'Member';

// User status
export type UserStatus = 'active' | 'inactive' | 'suspended';

// Authentication types
export interface LoginRequest {
  email: string;
  password: string;
}

export interface LoginResponse {
  access_token: string;
  token_type: string;
  expires_in: number;
  user: User;
}

export interface User {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  status: UserStatus;
  created_at: string;
  updated_at: string;
}

export interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;
}

// API Error types
export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
  status?: number;
}

// Member types
export interface Member {
  id: number;
  user_id: number;
  member_number: string;
  first_name: string;
  last_name: string;
  phone: string;
  address: string;
  employment_info: string;
  status: 'active' | 'inactive';
  created_at: string;
  updated_at: string;
}

// Loan types
export interface Loan {
  id: number;
  member_id: number;
  loan_number: string;
  amount: number;
  interest_rate: number;
  term_months: number;
  purpose: string;
  status: 'pending' | 'approved' | 'rejected' | 'disbursed' | 'active' | 'paid' | 'overdue';
  application_date: string;
  approval_date: string | null;
  disbursement_date: string | null;
  created_at: string;
  updated_at: string;
}

// Savings types
export interface SavingsAccount {
  id: number;
  member_id: number;
  account_number: string;
  balance: number;
  status: 'pending' | 'active' | 'suspended' | 'closed';
  rejection_reason?: string;
  created_at: string;
  updated_at: string;
}

export interface SavingsTransaction {
  id: number;
  account_id: number;
  type: 'deposit' | 'withdrawal';
  amount: number;
  source: string;
  reference: string;
  transaction_date: string;
  created_at: string;
  updated_at: string;
}

// Notification types
export interface Notification {
  id: number;
  user_id: number;
  type: string;
  channel: 'email' | 'sms' | 'in_app';
  subject: string;
  message: string;
  status: 'pending' | 'sent' | 'failed';
  sent_at: string | null;
  created_at: string;
  updated_at: string;
}

// Menu item type for navigation
export interface MenuItem {
  id: string;
  label: string;
  path: string;
  icon?: string;
  roles: UserRole[];
  children?: MenuItem[];
}

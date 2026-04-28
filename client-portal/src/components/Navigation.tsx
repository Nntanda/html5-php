import { Link, useLocation } from 'react-router-dom';
import { useAuthStore } from '../store/authStore';
import { MenuItem } from '../types';
import { Logo } from './Logo';
import { useState } from 'react';

interface NavigationProps {
  onClose?: () => void;
}

export const Navigation: React.FC<NavigationProps> = ({ onClose }) => {
  const location = useLocation();
  const { user } = useAuthStore();
  const [isCollapsed, setIsCollapsed] = useState(false);

  const menuItems: MenuItem[] = [
    {
      id: 'dashboard',
      label: 'Dashboard',
      path: '/dashboard',
      icon: 'dashboard',
      roles: ['Member', 'SuperAdmin', 'LoanOfficer', 'Accountant'],
    },
    {
      id: 'profile',
      label: 'My Profile',
      path: '/profile',
      icon: 'profile',
      roles: ['Member', 'SuperAdmin', 'LoanOfficer', 'Accountant'],
    },
    {
      id: 'savings',
      label: 'My Savings',
      path: '/savings',
      icon: 'savings',
      roles: ['Member', 'SuperAdmin', 'LoanOfficer', 'Accountant'],
    },
    {
      id: 'loans',
      label: 'My Loans',
      path: '/loans',
      icon: 'loans',
      roles: ['Member', 'SuperAdmin', 'LoanOfficer', 'Accountant'],
    },
    {
      id: 'transactions',
      label: 'Transactions',
      path: '/transactions',
      icon: 'transactions',
      roles: ['Member', 'SuperAdmin', 'LoanOfficer', 'Accountant'],
    },
    {
      id: 'statements',
      label: 'Statements',
      path: '/statements',
      icon: 'statements',
      roles: ['Member', 'SuperAdmin', 'LoanOfficer', 'Accountant'],
    },
    {
      id: 'reports',
      label: 'Reports',
      path: '/reports',
      icon: 'reports',
      roles: ['Member', 'SuperAdmin', 'LoanOfficer', 'Accountant'],
    },
    {
      id: 'guarantor-requests',
      label: 'Guarantor Requests',
      path: '/guarantor-requests',
      icon: 'guarantor',
      roles: ['Member', 'SuperAdmin', 'LoanOfficer', 'Accountant'],
    },
    {
      id: 'notifications',
      label: 'Notifications',
      path: '/notifications',
      icon: 'notifications',
      roles: ['Member', 'SuperAdmin', 'LoanOfficer', 'Accountant'],
    },
    {
      id: 'support',
      label: 'Help & Support',
      path: '/support',
      icon: 'support',
      roles: ['Member', 'SuperAdmin', 'LoanOfficer', 'Accountant'],
    },
  ];

  // Always show all items regardless of role for client portal
  const visibleItems = menuItems;

  const isActive = (path: string) => location.pathname === path;

  const getIcon = (iconName: string) => {
    const icons: Record<string, JSX.Element> = {
      dashboard: (
        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
      ),
      profile: (
        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
      ),
      savings: (
        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
      ),
      loans: (
        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      ),
      transactions: (
        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
        </svg>
      ),
      statements: (
        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      ),
      reports: (
        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      ),
      guarantor: (
        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
      ),
      notifications: (
        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
      ),
      support: (
        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
      ),
    };
    return icons[iconName] || icons.dashboard;
  };

  const handleNavClick = () => {
    if (onClose) {
      onClose();
    }
  };

  return (
    <nav className={`bg-gradient-to-b from-green-700 to-green-800 text-white flex flex-col h-full transition-all duration-300 ${
      isCollapsed ? 'w-20' : 'w-64'
    }`}>
      {/* Header */}
      <div className="p-4 border-b border-green-600 flex-shrink-0 relative">
        <div className="flex items-center justify-between">
          {!isCollapsed ? (
            <div className="flex items-center gap-3">
              <Logo size="md" />
              <div>
                <h2 className="text-xl font-bold">Kitovu Hospital</h2>
                <p className="text-green-100 text-xs">Staff Saving Scheme</p>
              </div>
            </div>
          ) : (
            <div className="mx-auto">
              <Logo size="sm" />
            </div>
          )}
        </div>
        
        {/* Close button for mobile */}
        {onClose && (
          <button
            onClick={onClose}
            className="lg:hidden absolute top-4 right-4 p-2 text-white hover:bg-green-600 rounded-lg transition-colors"
            aria-label="Close menu"
          >
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        )}
        
        {/* Collapse toggle - hidden on mobile */}
        {!onClose && (
          <button
            onClick={() => setIsCollapsed(!isCollapsed)}
            className="absolute -right-3 top-20 bg-green-600 text-white p-1.5 rounded-full shadow-lg hover:bg-green-500 transition-colors z-10"
            aria-label={isCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}
          >
            <svg
              className={`w-4 h-4 transition-transform ${isCollapsed ? 'rotate-180' : ''}`}
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
          </button>
        )}
      </div>

      {/* Navigation Items */}
      <div className="flex-1 overflow-y-auto py-4 scrollbar-thin scrollbar-thumb-green-600 scrollbar-track-green-800">
        {visibleItems.map((item) => (
          <Link
            key={item.id}
            to={item.path}
            onClick={handleNavClick}
            className={`flex items-center px-4 py-3 mx-2 rounded-lg transition-all duration-200 group ${
              isActive(item.path)
                ? 'bg-green-600 shadow-lg'
                : 'hover:bg-green-600/50'
            }`}
            title={isCollapsed ? item.label : ''}
          >
            <span className={`flex-shrink-0 ${isCollapsed ? 'mx-auto' : ''}`}>
              {getIcon(item.icon)}
            </span>
            {!isCollapsed && (
              <span className="ml-3 font-medium">{item.label}</span>
            )}
            {isActive(item.path) && !isCollapsed && (
              <span className="ml-auto">
                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clipRule="evenodd" />
                </svg>
              </span>
            )}
          </Link>
        ))}
      </div>

      {/* User Profile Section */}
      {!isCollapsed ? (
        <div className="p-4 border-t border-green-600 flex-shrink-0">
          <div className="flex items-center gap-3 p-3 bg-green-600/30 rounded-lg">
            <div className="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-bold">
              {user?.name?.charAt(0).toUpperCase() || 'M'}
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-semibold truncate">{user?.name || 'Member'}</p>
              <p className="text-xs text-green-100 truncate">{user?.email}</p>
            </div>
          </div>
        </div>
      ) : (
        <div className="p-4 border-t border-green-600 flex-shrink-0">
          <div className="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-bold mx-auto">
            {user?.name?.charAt(0).toUpperCase() || 'M'}
          </div>
        </div>
      )}
    </nav>
  );
};

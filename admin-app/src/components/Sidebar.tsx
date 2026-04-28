import { Link, useLocation } from 'react-router-dom';
import { useAuthStore } from '../store/authStore';
import { MenuItem, UserRole } from '../types';
import { Logo } from './Logo';
import { useState } from 'react';

// SVG Icon Components
const DashboardIcon = () => (
  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
  </svg>
);

const UsersIcon = () => (
  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
  </svg>
);

const MembersIcon = () => (
  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
  </svg>
);

const SavingsIcon = () => (
  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
  </svg>
);

const LoansIcon = () => (
  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
  </svg>
);

const RepaymentsIcon = () => (
  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
  </svg>
);

const ReportsIcon = () => (
  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
  </svg>
);

const NotificationsIcon = () => (
  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
  </svg>
);

const ConfigIcon = () => (
  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
  </svg>
);

const AuditIcon = () => (
  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
  </svg>
);

const BackupsIcon = () => (
  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
  </svg>
);

const menuItems: MenuItem[] = [
  {
    id: 'dashboard',
    label: 'Dashboard',
    path: '/dashboard',
    icon: 'dashboard',
    roles: ['super_admin', 'loan_officer', 'accountant'],
  },
  {
    id: 'users',
    label: 'Users',
    path: '/users',
    icon: 'users',
    roles: ['super_admin'],
  },
  {
    id: 'members',
    label: 'Members',
    path: '/members',
    icon: 'members',
    roles: ['super_admin', 'loan_officer', 'accountant'],
  },
  {
    id: 'savings',
    label: 'Savings',
    path: '/savings',
    icon: 'savings',
    roles: ['super_admin', 'loan_officer', 'accountant'],
    subItems: [
      {
        id: 'savings-management',
        label: 'Savings Management',
        path: '/savings',
        roles: ['super_admin', 'loan_officer', 'accountant'],
      },
      {
        id: 'create-savings-account',
        label: 'Create Savings Account',
        path: '/savings/create-account',
        roles: ['super_admin', 'loan_officer', 'accountant'],
      },
    ],
  },
  {
    id: 'loans',
    label: 'Loans',
    path: '/loans',
    icon: 'loans',
    roles: ['super_admin', 'loan_officer', 'accountant'],
  },
  {
    id: 'repayments',
    label: 'Repayments',
    path: '/repayments',
    icon: 'repayments',
    roles: ['super_admin', 'loan_officer', 'accountant'],
  },
  {
    id: 'reports',
    label: 'Reports',
    path: '/reports',
    icon: 'reports',
    roles: ['super_admin', 'loan_officer', 'accountant'],
  },
  {
    id: 'notifications',
    label: 'Notifications',
    path: '/notifications',
    icon: 'notifications',
    roles: ['super_admin', 'loan_officer', 'accountant'],
  },
  {
    id: 'config',
    label: 'Configuration',
    path: '/config',
    icon: 'config',
    roles: ['super_admin'],
  },
  {
    id: 'audit',
    label: 'Audit Logs',
    path: '/audit',
    icon: 'audit',
    roles: ['super_admin'],
  },
  {
    id: 'backups',
    label: 'Backups',
    path: '/backups',
    icon: 'backups',
    roles: ['super_admin'],
  },
];

const getIcon = (iconName: string) => {
  const icons: { [key: string]: JSX.Element } = {
    dashboard: <DashboardIcon />,
    users: <UsersIcon />,
    members: <MembersIcon />,
    savings: <SavingsIcon />,
    loans: <LoansIcon />,
    repayments: <RepaymentsIcon />,
    reports: <ReportsIcon />,
    notifications: <NotificationsIcon />,
    config: <ConfigIcon />,
    audit: <AuditIcon />,
    backups: <BackupsIcon />,
  };
  return icons[iconName] || <DashboardIcon />;
};

export const Sidebar: React.FC<{ onClose?: () => void }> = ({ onClose }) => {
  const { user } = useAuthStore();
  const location = useLocation();
  const [isCollapsed, setIsCollapsed] = useState(false);
  const [expandedItems, setExpandedItems] = useState<string[]>(['savings']); // Savings expanded by default

  const visibleItems = menuItems.filter((item) =>
    user ? item.roles.includes(user.role as UserRole) : false
  );

  const toggleExpanded = (itemId: string) => {
    setExpandedItems(prev => 
      prev.includes(itemId) 
        ? prev.filter(id => id !== itemId)
        : [...prev, itemId]
    );
  };

  return (
    <aside className={`${isCollapsed ? 'w-16' : 'w-64'} bg-gradient-to-b from-indigo-900 via-indigo-800 to-indigo-900 text-white shadow-2xl transition-all duration-300 ease-in-out flex flex-col h-full`}>
      {/* Header */}
      <div className="p-4 border-b border-indigo-700/50 flex items-center justify-between">
        {!isCollapsed ? (
          <div className="flex items-center gap-2">
            <Logo size="sm" />
            <div>
              <h1 className="text-lg font-bold text-white">Kitovu Hospital</h1>
              <p className="text-xs text-indigo-300">Admin Portal</p>
            </div>
          </div>
        ) : (
          <div className="mx-auto">
            <Logo size="sm" />
          </div>
        )}
        {/* Close button for mobile */}
        <button
          onClick={onClose}
          className="lg:hidden text-white hover:text-indigo-300"
        >
          <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      {/* Toggle Button - Hidden on mobile */}
      <button
        onClick={() => setIsCollapsed(!isCollapsed)}
        className="hidden lg:block py-2 text-center text-indigo-300 hover:text-white hover:bg-indigo-800/50 transition-colors text-xs border-b border-indigo-700/30"
      >
        {isCollapsed ? '→' : '←'}
      </button>

      {/* Navigation */}
      <nav className="flex-1 py-4 px-2 space-y-1 overflow-y-auto">
        {visibleItems.map((item) => {
          const isActive = location.pathname === item.path;
          const hasSubItems = item.subItems && item.subItems.length > 0;
          const isExpanded = expandedItems.includes(item.id);
          const isSubItemActive = hasSubItems && item.subItems?.some(sub => location.pathname === sub.path);

          return (
            <div key={item.id}>
              {/* Main Menu Item */}
              {hasSubItems && !isCollapsed ? (
                <button
                  onClick={() => toggleExpanded(item.id)}
                  className={`w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group ${
                    isSubItemActive
                      ? 'bg-indigo-800/50'
                      : 'hover:bg-indigo-800/50'
                  }`}
                >
                  <div className="flex items-center gap-3">
                    <span className={`${isSubItemActive ? 'text-white' : 'text-indigo-200 group-hover:text-white'} transition-colors flex-shrink-0`}>
                      {getIcon(item.icon)}
                    </span>
                    <span className={`text-sm font-medium ${isSubItemActive ? 'text-white' : 'text-indigo-100'} truncate`}>
                      {item.label}
                    </span>
                  </div>
                  <svg 
                    className={`w-4 h-4 text-indigo-300 transition-transform ${isExpanded ? 'rotate-180' : ''}`}
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                  >
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
              ) : (
                <Link
                  to={item.path}
                  onClick={onClose}
                  className={`flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group ${
                    isActive
                      ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 shadow-lg shadow-indigo-500/30'
                      : 'hover:bg-indigo-800/50'
                  }`}
                  title={isCollapsed ? item.label : ''}
                >
                  <span className={`${isActive ? 'text-white' : 'text-indigo-200 group-hover:text-white'} transition-colors flex-shrink-0`}>
                    {getIcon(item.icon)}
                  </span>
                  {!isCollapsed && (
                    <span className={`text-sm font-medium ${isActive ? 'text-white' : 'text-indigo-100'} truncate`}>
                      {item.label}
                    </span>
                  )}
                </Link>
              )}

              {/* Sub Menu Items */}
              {hasSubItems && !isCollapsed && isExpanded && (
                <div className="ml-8 mt-1 space-y-1">
                  {item.subItems?.filter(subItem => 
                    user ? subItem.roles.includes(user.role as UserRole) : false
                  ).map((subItem) => {
                    const isSubActive = location.pathname === subItem.path;
                    return (
                      <Link
                        key={subItem.id}
                        to={subItem.path}
                        onClick={onClose}
                        className={`flex items-center gap-2 px-3 py-2 rounded-lg transition-all duration-200 group ${
                          isSubActive
                            ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 shadow-lg shadow-indigo-500/30'
                            : 'hover:bg-indigo-800/50'
                        }`}
                      >
                        <span className={`text-xs font-medium ${isSubActive ? 'text-white' : 'text-indigo-200 group-hover:text-white'} truncate`}>
                          {subItem.label}
                        </span>
                      </Link>
                    );
                  })}
                </div>
              )}
            </div>
          );
        })}
      </nav>

      {/* User Info at Bottom */}
      {user && (
        <div className="p-3 border-t border-indigo-700/50 bg-indigo-900/50">
          {!isCollapsed ? (
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                {user.name.charAt(0).toUpperCase()}
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-xs font-medium text-white truncate">{user.name}</p>
                <p className="text-xs text-indigo-300 truncate">{user.email}</p>
              </div>
            </div>
          ) : (
            <div className="w-8 h-8 mx-auto rounded-full bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center text-white font-bold text-sm">
              {user.name.charAt(0).toUpperCase()}
            </div>
          )}
        </div>
      )}
    </aside>
  );
};

import React, { useState, useEffect } from 'react';
import { apiClient } from '../api/client';
import { Notification } from '../types';

type FilterStatus = 'all' | 'unread' | 'read';

export const Notifications: React.FC = () => {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [filterStatus, setFilterStatus] = useState<FilterStatus>('all');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [selectedNotification, setSelectedNotification] = useState<Notification | null>(null);

  useEffect(() => {
    fetchNotifications(1);
  }, [filterStatus]);

  const fetchNotifications = async (page = 1) => {
    setIsLoading(true);
    setError(null);
    try {
      const params: any = { page, per_page: 20 };
      
      if (filterStatus === 'unread') {
        params.status = 'pending';
      } else if (filterStatus === 'read') {
        params.status = 'sent';
      }

      const response = await apiClient.get<{
        data: Notification[];
        meta: { current_page: number; last_page: number };
      }>('/notifications', { params });

      setNotifications(response.data.data);
      setTotalPages(response.data.meta.last_page);
      setCurrentPage(page);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to fetch notifications');
    } finally {
      setIsLoading(false);
    }
  };

  const markAsRead = async (notificationId: number) => {
    try {
      await apiClient.put(`/notifications/${notificationId}/read`);
      setNotifications(notifications.map((n: Notification) =>
        n.id === notificationId ? { ...n, status: 'sent' } : n
      ));
      if (selectedNotification?.id === notificationId) {
        setSelectedNotification({ ...selectedNotification, status: 'sent' });
      }
    } catch (err: any) {
      setError('Failed to mark notification as read');
    }
  };

  const markAllAsRead = async () => {
    try {
      const unreadIds = notifications
        .filter((n: Notification) => n.status === 'pending')
        .map((n: Notification) => n.id);

      for (const id of unreadIds) {
        await apiClient.put(`/notifications/${id}/read`);
      }

      setNotifications(notifications.map((n: Notification) => ({
        ...n,
        status: 'sent'
      })));
    } catch (err: any) {
      setError('Failed to mark notifications as read');
    }
  };

  const getStatusColor = (status: string) => {
    return status === 'pending' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800';
  };

  const getChannelIcon = (channel: string) => {
    switch (channel) {
      case 'email':
        return '✉️';
      case 'sms':
        return '📱';
      case 'in_app':
        return '🔔';
      default:
        return '📬';
    }
  };

  const unreadCount = notifications.filter((n: Notification) => n.status === 'pending').length;

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Notifications</h1>
          <p className="text-gray-600 mt-2">View and manage your notifications</p>
        </div>
        {unreadCount > 0 && (
          <button
            onClick={markAllAsRead}
            className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium"
          >
            Mark All as Read
          </button>
        )}
      </div>

      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {error}
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2">
          <div className="bg-white rounded-lg shadow">
            <div className="p-4 border-b">
              <div className="flex gap-2">
                <button
                  onClick={() => setFilterStatus('all')}
                  className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                    filterStatus === 'all'
                      ? 'bg-green-600 text-white'
                      : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                  }`}
                >
                  All
                </button>
                <button
                  onClick={() => setFilterStatus('unread')}
                  className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                    filterStatus === 'unread'
                      ? 'bg-green-600 text-white'
                      : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                  }`}
                >
                  Unread ({unreadCount})
                </button>
                <button
                  onClick={() => setFilterStatus('read')}
                  className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                    filterStatus === 'read'
                      ? 'bg-green-600 text-white'
                      : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                  }`}
                >
                  Read
                </button>
              </div>
            </div>

            {isLoading ? (
              <div className="p-6 text-center text-gray-500">Loading notifications...</div>
            ) : notifications.length === 0 ? (
              <div className="p-6 text-center text-gray-500">No notifications</div>
            ) : (
              <>
                <div className="divide-y">
                  {notifications.map((notification: Notification) => (
                    <div
                      key={notification.id}
                      onClick={() => {
                        setSelectedNotification(notification);
                        if (notification.status === 'pending') {
                          markAsRead(notification.id);
                        }
                      }}
                      className={`p-4 cursor-pointer hover:bg-gray-50 transition-colors ${
                        notification.status === 'pending' ? 'bg-blue-50' : ''
                      } ${selectedNotification?.id === notification.id ? 'border-l-4 border-green-600' : ''}`}
                    >
                      <div className="flex justify-between items-start">
                        <div className="flex-1">
                          <div className="flex items-center gap-2">
                            <span className="text-lg">{getChannelIcon(notification.channel)}</span>
                            <p className="font-medium text-gray-900">{notification.subject}</p>
                          </div>
                          <p className="text-sm text-gray-600 mt-1">{notification.message}</p>
                          <p className="text-xs text-gray-500 mt-2">
                            {new Date(notification.created_at).toLocaleString()}
                          </p>
                        </div>
                        <div className="flex items-center gap-2 ml-4">
                          <span
                            className={`px-2 py-1 rounded text-xs font-medium ${getStatusColor(
                              notification.status
                            )}`}
                          >
                            {notification.status === 'pending' ? 'Unread' : 'Read'}
                          </span>
                          {notification.status === 'pending' && (
                            <div className="w-2 h-2 bg-blue-600 rounded-full"></div>
                          )}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>

                {totalPages > 1 && (
                  <div className="px-6 py-4 border-t flex justify-between items-center">
                    <button
                      onClick={() => fetchNotifications(currentPage - 1)}
                      disabled={currentPage === 1}
                      className="px-3 py-1 border border-gray-300 rounded disabled:opacity-50 hover:bg-gray-50"
                    >
                      Previous
                    </button>
                    <span className="text-sm text-gray-600">
                      Page {currentPage} of {totalPages}
                    </span>
                    <button
                      onClick={() => fetchNotifications(currentPage + 1)}
                      disabled={currentPage === totalPages}
                      className="px-3 py-1 border border-gray-300 rounded disabled:opacity-50 hover:bg-gray-50"
                    >
                      Next
                    </button>
                  </div>
                )}
              </>
            )}
          </div>
        </div>

        <div>
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">Details</h3>
            {selectedNotification ? (
              <div className="space-y-4">
                <div>
                  <p className="text-sm text-gray-600">Subject</p>
                  <p className="font-medium text-gray-900">{selectedNotification.subject}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Message</p>
                  <p className="text-gray-900">{selectedNotification.message}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Channel</p>
                  <p className="font-medium text-gray-900">
                    {getChannelIcon(selectedNotification.channel)} {selectedNotification.channel}
                  </p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Status</p>
                  <p className="font-medium text-gray-900">
                    {selectedNotification.status === 'pending' ? 'Unread' : 'Read'}
                  </p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Sent At</p>
                  <p className="text-gray-900">
                    {selectedNotification.sent_at
                      ? new Date(selectedNotification.sent_at).toLocaleString()
                      : 'Not sent yet'}
                  </p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Created At</p>
                  <p className="text-gray-900">
                    {new Date(selectedNotification.created_at).toLocaleString()}
                  </p>
                </div>
                {selectedNotification.status === 'pending' && (
                  <button
                    onClick={() => markAsRead(selectedNotification.id)}
                    className="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium"
                  >
                    Mark as Read
                  </button>
                )}
              </div>
            ) : (
              <p className="text-gray-500 text-center">Select a notification to view details</p>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

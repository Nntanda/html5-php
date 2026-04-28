import React, { useState, useEffect } from 'react';
import { apiClient } from '../api/client';

interface AuditLog {
  id: number;
  user_id: number;
  user_name?: string;
  action: string;
  entity_type: string;
  entity_id: string;
  changes: Record<string, any>;
  ip_address: string;
  created_at: string;
}

export const Audit: React.FC = () => {
  const [auditLogs, setAuditLogs] = useState<AuditLog[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  // Filters
  const [filterUser, setFilterUser] = useState('');
  const [filterAction, setFilterAction] = useState('');
  const [filterStartDate, setFilterStartDate] = useState('');
  const [filterEndDate, setFilterEndDate] = useState('');
  const [users, setUsers] = useState<Array<{ id: number; name: string }>>([]);
  const [actions, setActions] = useState<string[]>([]);

  // Selected log for details
  const [selectedLog, setSelectedLog] = useState<AuditLog | null>(null);

  useEffect(() => {
    fetchAuditLogs(1);
    fetchFilterOptions();
  }, []);

  useEffect(() => {
    fetchAuditLogs(1);
  }, [filterUser, filterAction, filterStartDate, filterEndDate]);

  const fetchFilterOptions = async () => {
    try {
      const response = await apiClient.get<{
        users: Array<{ id: number; name: string }>;
        actions: string[];
      }>('/audit-logs/filters');
      setUsers(response.data.users);
      setActions(response.data.actions);
    } catch (err: any) {
      console.error('Failed to fetch filter options');
    }
  };

  const fetchAuditLogs = async (page = 1) => {
    setIsLoading(true);
    setError(null);
    try {
      const params: any = { page, per_page: 20 };
      if (filterUser) params.user_id = filterUser;
      if (filterAction) params.action = filterAction;
      if (filterStartDate) params.start_date = filterStartDate;
      if (filterEndDate) params.end_date = filterEndDate;

      const response = await apiClient.get<{
        data: AuditLog[];
        meta: { current_page: number; last_page: number };
      }>('/audit-logs', { params });

      setAuditLogs(response.data.data);
      setTotalPages(response.data.meta.last_page);
      setCurrentPage(page);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to fetch audit logs');
    } finally {
      setIsLoading(false);
    }
  };

  const getActionColor = (action: string) => {
    switch (action.toLowerCase()) {
      case 'create':
        return 'bg-green-100 text-green-800';
      case 'update':
        return 'bg-blue-100 text-blue-800';
      case 'delete':
        return 'bg-red-100 text-red-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const formatChanges = (changes: Record<string, any>) => {
    if (!changes || Object.keys(changes).length === 0) {
      return 'No changes';
    }
    return Object.entries(changes)
      .map(([key, value]) => `${key}: ${JSON.stringify(value)}`)
      .join(', ');
  };

  return (
    <div>
      <h1 className="text-3xl font-bold text-gray-800 mb-6">Audit Logs</h1>

      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          {error}
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2">
          <div className="bg-white rounded-lg shadow">
            <div className="p-4 border-b">
              <h3 className="text-lg font-semibold text-gray-800 mb-4">Filters</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <select
                  value={filterUser}
                  onChange={(e) => setFilterUser(e.target.value)}
                  className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">All Users</option>
                  {users.map(user => (
                    <option key={user.id} value={user.id}>
                      {user.name}
                    </option>
                  ))}
                </select>

                <select
                  value={filterAction}
                  onChange={(e) => setFilterAction(e.target.value)}
                  className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">All Actions</option>
                  {actions.map(action => (
                    <option key={action} value={action}>
                      {action}
                    </option>
                  ))}
                </select>

                <input
                  type="date"
                  value={filterStartDate}
                  onChange={(e) => setFilterStartDate(e.target.value)}
                  className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Start Date"
                />

                <input
                  type="date"
                  value={filterEndDate}
                  onChange={(e) => setFilterEndDate(e.target.value)}
                  className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="End Date"
                />
              </div>
            </div>

            {isLoading ? (
              <div className="p-6 text-center text-gray-500">Loading audit logs...</div>
            ) : auditLogs.length === 0 ? (
              <div className="p-6 text-center text-gray-500">No audit logs found</div>
            ) : (
              <>
                <div className="overflow-x-auto">
                  <table className="w-full">
                    <thead className="bg-gray-50 border-b">
                      <tr>
                        <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                          User
                        </th>
                        <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                          Action
                        </th>
                        <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                          Entity
                        </th>
                        <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                          IP Address
                        </th>
                        <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                          Timestamp
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      {auditLogs.map((log) => (
                        <tr
                          key={log.id}
                          onClick={() => setSelectedLog(log)}
                          className={`border-b hover:bg-gray-50 cursor-pointer ${
                            selectedLog?.id === log.id ? 'bg-blue-50' : ''
                          }`}
                        >
                          <td className="px-6 py-4 text-sm text-gray-900">
                            {log.user_name || `User #${log.user_id}`}
                          </td>
                          <td className="px-6 py-4 text-sm">
                            <span className={`px-2 py-1 rounded text-xs font-medium ${getActionColor(log.action)}`}>
                              {log.action}
                            </span>
                          </td>
                          <td className="px-6 py-4 text-sm text-gray-600">
                            {log.entity_type} #{log.entity_id}
                          </td>
                          <td className="px-6 py-4 text-sm text-gray-600">{log.ip_address}</td>
                          <td className="px-6 py-4 text-sm text-gray-600">
                            {new Date(log.created_at).toLocaleString()}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>

                {totalPages > 1 && (
                  <div className="px-6 py-4 border-t flex justify-between items-center">
                    <button
                      onClick={() => fetchAuditLogs(currentPage - 1)}
                      disabled={currentPage === 1}
                      className="px-3 py-1 border border-gray-300 rounded disabled:opacity-50"
                    >
                      Previous
                    </button>
                    <span className="text-sm text-gray-600">
                      Page {currentPage} of {totalPages}
                    </span>
                    <button
                      onClick={() => fetchAuditLogs(currentPage + 1)}
                      disabled={currentPage === totalPages}
                      className="px-3 py-1 border border-gray-300 rounded disabled:opacity-50"
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
            {selectedLog ? (
              <div className="space-y-4">
                <div>
                  <p className="text-sm text-gray-600">User</p>
                  <p className="font-medium text-gray-900">
                    {selectedLog.user_name || `User #${selectedLog.user_id}`}
                  </p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Action</p>
                  <p className={`font-medium px-2 py-1 rounded text-sm w-fit ${getActionColor(selectedLog.action)}`}>
                    {selectedLog.action}
                  </p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Entity Type</p>
                  <p className="font-medium text-gray-900">{selectedLog.entity_type}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Entity ID</p>
                  <p className="font-medium text-gray-900">{selectedLog.entity_id}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">IP Address</p>
                  <p className="font-medium text-gray-900">{selectedLog.ip_address}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Timestamp</p>
                  <p className="font-medium text-gray-900">
                    {new Date(selectedLog.created_at).toLocaleString()}
                  </p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Changes</p>
                  <p className="text-sm text-gray-900 bg-gray-50 p-2 rounded break-words">
                    {formatChanges(selectedLog.changes)}
                  </p>
                </div>
              </div>
            ) : (
              <p className="text-gray-500 text-center">Select a log entry to view details</p>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

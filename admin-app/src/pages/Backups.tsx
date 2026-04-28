import React, { useState, useEffect } from 'react';
import { apiClient } from '../api/client';
import { ConfirmDialog } from '../components/ConfirmDialog';

interface Backup {
  id: string;
  filename: string;
  size: number;
  created_at: string;
  status: 'completed' | 'failed' | 'pending';
}

export const Backups: React.FC = () => {
  const [backups, setBackups] = useState<Backup[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [isCreatingBackup, setIsCreatingBackup] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  // Restore dialog
  const [isRestoreDialogOpen, setIsRestoreDialogOpen] = useState(false);
  const [backupToRestore, setBackupToRestore] = useState<Backup | null>(null);
  const [isRestoring, setIsRestoring] = useState(false);

  useEffect(() => {
    fetchBackups(1);
  }, []);

  const fetchBackups = async (page = 1) => {
    setIsLoading(true);
    setError(null);
    try {
      const response = await apiClient.get<{
        data: Backup[];
        meta: { current_page: number; last_page: number };
      }>('/backups', { params: { page, per_page: 10 } });

      setBackups(response.data.data);
      setTotalPages(response.data.meta.last_page);
      setCurrentPage(page);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to fetch backups');
    } finally {
      setIsLoading(false);
    }
  };

  const handleCreateBackup = async () => {
    setIsCreatingBackup(true);
    setError(null);
    setSuccess(null);
    try {
      await apiClient.post('/backups/create');
      setSuccess('Backup created successfully');
      setTimeout(() => setSuccess(null), 3000);
      fetchBackups(1);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to create backup');
    } finally {
      setIsCreatingBackup(false);
    }
  };

  const openRestoreDialog = (backup: Backup) => {
    setBackupToRestore(backup);
    setIsRestoreDialogOpen(true);
  };

  const handleRestore = async () => {
    if (!backupToRestore) return;

    setIsRestoring(true);
    setError(null);
    setSuccess(null);
    try {
      await apiClient.post(`/backups/${backupToRestore.id}/restore`);
      setSuccess('Backup restored successfully');
      setIsRestoreDialogOpen(false);
      setBackupToRestore(null);
      setTimeout(() => setSuccess(null), 3000);
      fetchBackups(currentPage);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to restore backup');
    } finally {
      setIsRestoring(false);
    }
  };

  const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'completed':
        return 'bg-green-100 text-green-800';
      case 'failed':
        return 'bg-red-100 text-red-800';
      case 'pending':
        return 'bg-yellow-100 text-yellow-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold text-gray-800">Backups</h1>
        <button
          onClick={handleCreateBackup}
          disabled={isCreatingBackup}
          className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50"
        >
          {isCreatingBackup ? 'Creating...' : '+ Create Backup'}
        </button>
      </div>

      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          {error}
        </div>
      )}

      {success && (
        <div className="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
          {success}
        </div>
      )}

      <div className="bg-white rounded-lg shadow overflow-hidden">
        {isLoading ? (
          <div className="p-6 text-center text-gray-500">Loading backups...</div>
        ) : backups.length === 0 ? (
          <div className="p-6 text-center text-gray-500">No backups found</div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead className="bg-gray-50 border-b">
                  <tr>
                    <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                      Backup ID
                    </th>
                    <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                      Filename
                    </th>
                    <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                      Size
                    </th>
                    <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                      Status
                    </th>
                    <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                      Created Date
                    </th>
                    <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {backups.map((backup) => (
                    <tr key={backup.id} className="border-b hover:bg-gray-50">
                      <td className="px-6 py-4 text-sm text-gray-900">{backup.id}</td>
                      <td className="px-6 py-4 text-sm text-gray-600">{backup.filename}</td>
                      <td className="px-6 py-4 text-sm text-gray-600">
                        {formatFileSize(backup.size)}
                      </td>
                      <td className="px-6 py-4 text-sm">
                        <span className={`px-2 py-1 rounded text-xs font-medium ${getStatusColor(backup.status)}`}>
                          {backup.status}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-600">
                        {new Date(backup.created_at).toLocaleString()}
                      </td>
                      <td className="px-6 py-4 text-sm">
                        <button
                          onClick={() => openRestoreDialog(backup)}
                          disabled={backup.status !== 'completed'}
                          className="text-blue-600 hover:text-blue-800 disabled:text-gray-400 disabled:cursor-not-allowed"
                        >
                          Restore
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {totalPages > 1 && (
              <div className="px-6 py-4 border-t flex justify-between items-center">
                <button
                  onClick={() => fetchBackups(currentPage - 1)}
                  disabled={currentPage === 1}
                  className="px-3 py-1 border border-gray-300 rounded disabled:opacity-50"
                >
                  Previous
                </button>
                <span className="text-sm text-gray-600">
                  Page {currentPage} of {totalPages}
                </span>
                <button
                  onClick={() => fetchBackups(currentPage + 1)}
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

      <ConfirmDialog
        isOpen={isRestoreDialogOpen}
        title="Restore Backup"
        message={`Are you sure you want to restore from backup ${backupToRestore?.filename}? This will overwrite the current database.`}
        onConfirm={handleRestore}
        onCancel={() => {
          setIsRestoreDialogOpen(false);
          setBackupToRestore(null);
        }}
        isLoading={isRestoring}
        confirmText="Restore"
        isDangerous
      />
    </div>
  );
};

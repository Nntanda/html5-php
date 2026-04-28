import React, { useState, useEffect } from 'react';
import { apiClient } from '../api/client';
import { Member } from '../types';
import { Modal } from '../components/Modal';
import { ConfirmDialog } from '../components/ConfirmDialog';
import { MemberForm } from '../components/MemberForm';
import { MemberProfileView } from '../components/MemberProfileView';

interface MemberWithSummary extends Member {
  financial_summary?: {
    savings_balance: number;
    active_loans: number;
  };
  has_savings_account?: boolean;
}

export const Members: React.FC = () => {
  const [members, setMembers] = useState<MemberWithSummary[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState<'active' | 'inactive' | ''>('');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isProfileModalOpen, setIsProfileModalOpen] = useState(false);
  const [selectedMember, setSelectedMember] = useState<MemberWithSummary | null>(null);
  const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
  const [memberToDelete, setMemberToDelete] = useState<MemberWithSummary | null>(null);
  const [isDeleting, setIsDeleting] = useState(false);
  
  // Approval states
  const [isApproveDialogOpen, setIsApproveDialogOpen] = useState(false);
  const [isRejectDialogOpen, setIsRejectDialogOpen] = useState(false);
  const [memberToApprove, setMemberToApprove] = useState<MemberWithSummary | null>(null);
  const [memberToReject, setMemberToReject] = useState<MemberWithSummary | null>(null);
  const [isApproving, setIsApproving] = useState(false);
  const [isRejecting, setIsRejecting] = useState(false);
  const [rejectionReason, setRejectionReason] = useState('');
  const [creatingAccountFor, setCreatingAccountFor] = useState<number | null>(null);

  // Auto-dismiss messages after 5 seconds
  useEffect(() => {
    if (successMessage) {
      const timer = setTimeout(() => setSuccessMessage(null), 5000);
      return () => clearTimeout(timer);
    }
  }, [successMessage]);

  useEffect(() => {
    if (error) {
      const timer = setTimeout(() => setError(null), 8000);
      return () => clearTimeout(timer);
    }
  }, [error]);

  const fetchMembers = async (page = 1) => {
    setIsLoading(true);
    setError(null); // Clear any previous errors
    try {
      const params: any = { page, per_page: 10 };
      if (searchTerm) params.search = searchTerm;
      if (filterStatus) params.status = filterStatus;

      const response = await apiClient.get<{
        data: MemberWithSummary[];
        meta: { current_page: number; last_page: number };
      }>('/members', { params });

      setMembers(response.data.data);
      setTotalPages(response.data.meta.last_page);
      setCurrentPage(page);
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 
                          err.message || 
                          'Unable to load members. Please check your connection and try again.';
      setError(errorMessage);
      console.error('Fetch members error:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchMembers(1);
  }, [searchTerm, filterStatus]);

  const fetchMemberProfile = async (memberId: number) => {
    setError(null);
    try {
      const memberResponse = await apiClient.get<{ member: Member }>(`/members/${memberId}`);
      const summaryResponse = await apiClient.get<any>(`/members/${memberId}/summary`);
      
      const memberData = memberResponse.data.member as MemberWithSummary;
      memberData.financial_summary = {
        savings_balance: summaryResponse.data.savings?.total_balance || 0,
        active_loans: summaryResponse.data.loans?.active_loans_count || 0,
      };
      
      setSelectedMember(memberData);
      setIsProfileModalOpen(true);
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 
                          'Unable to load member profile. Please try again.';
      setError(errorMessage);
      console.error('Fetch member profile error:', err);
    }
  };

  const handleCreateMember = async (data: any) => {
    setError(null);
    try {
      await apiClient.post('/members', data);
      setIsCreateModalOpen(false);
      setSuccessMessage('✓ Member registered successfully!');
      fetchMembers(currentPage);
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 
                          'Failed to register member. Please check the form and try again.';
      throw new Error(errorMessage);
    }
  };

  const handleEditMember = async (data: any) => {
    if (!selectedMember) return;
    setError(null);
    try {
      await apiClient.put(`/members/${selectedMember.id}`, data);
      setIsEditModalOpen(false);
      setSelectedMember(null);
      setSuccessMessage('✓ Member information updated successfully!');
      fetchMembers(currentPage);
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 
                          'Failed to update member. Please try again.';
      throw new Error(errorMessage);
    }
  };

  const handleDeleteMember = async () => {
    if (!memberToDelete) return;
    setIsDeleting(true);
    setError(null);
    try {
      await apiClient.delete(`/members/${memberToDelete.id}`);
      setIsDeleteDialogOpen(false);
      const memberName = `${memberToDelete.first_name} ${memberToDelete.last_name}`;
      setMemberToDelete(null);
      setSuccessMessage(`✓ ${memberName} has been removed successfully.`);
      fetchMembers(currentPage);
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 
                          'Unable to delete member. They may have active loans or savings.';
      setError(errorMessage);
      console.error('Delete member error:', err);
    } finally {
      setIsDeleting(false);
    }
  };

  const openEditModal = (member: MemberWithSummary) => {
    setSelectedMember(member);
    setIsEditModalOpen(true);
  };

  const openDeleteDialog = (member: MemberWithSummary) => {
    setMemberToDelete(member);
    setIsDeleteDialogOpen(true);
  };

  const openApproveDialog = (member: MemberWithSummary) => {
    setMemberToApprove(member);
    setIsApproveDialogOpen(true);
  };

  const openRejectDialog = (member: MemberWithSummary) => {
    setMemberToReject(member);
    setRejectionReason('');
    setIsRejectDialogOpen(true);
  };

  const handleApproveMember = async () => {
    if (!memberToApprove) return;
    setIsApproving(true);
    setError(null);
    try {
      await apiClient.put(`/members/${memberToApprove.id}/approve`);
      setIsApproveDialogOpen(false);
      const memberName = `${memberToApprove.first_name} ${memberToApprove.last_name}`;
      setMemberToApprove(null);
      setSuccessMessage(`✓ ${memberName} has been approved and their account is now active!`);
      fetchMembers(currentPage);
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 
                          'Failed to approve member. Please try again.';
      setError(errorMessage);
      console.error('Approve member error:', err);
    } finally {
      setIsApproving(false);
    }
  };

  const handleRejectMember = async () => {
    if (!memberToReject) return;
    setIsRejecting(true);
    setError(null);
    try {
      await apiClient.post(`/members/${memberToReject.id}/reject`, {
        reason: rejectionReason,
      });
      setIsRejectDialogOpen(false);
      const memberName = `${memberToReject.first_name} ${memberToReject.last_name}`;
      setMemberToReject(null);
      setRejectionReason('');
      setSuccessMessage(`✓ Application for ${memberName} has been rejected.`);
      fetchMembers(currentPage);
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 
                          'Failed to reject member application. Please try again.';
      setError(errorMessage);
      console.error('Reject member error:', err);
    } finally {
      setIsRejecting(false);
    }
  };

  const handleCreateSavingsAccount = async (memberId: number) => {
    setCreatingAccountFor(memberId);
    setError(null);
    try {
      await apiClient.post('/savings/accounts/admin-create', {
        member_id: memberId
      });
      setSuccessMessage('✓ Savings account created successfully!');
      fetchMembers(currentPage);
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 
                          'Failed to create savings account. Member may already have an account.';
      setError(errorMessage);
      console.error('Create savings account error:', err);
    } finally {
      setCreatingAccountFor(null);
    }
  };

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-UG', {
      style: 'currency',
      currency: 'UGX',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(value);
  };

  return (
    <div className="space-y-4 sm:space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4 sm:mb-6">
        <h1 className="text-2xl sm:text-3xl font-bold text-gray-800">Members</h1>
        <button
          onClick={() => setIsCreateModalOpen(true)}
          className="w-full sm:w-auto bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 flex items-center justify-center gap-2"
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
          </svg>
          Register Member
        </button>
      </div>

      {/* Success Message */}
      {successMessage && (
        <div className="bg-green-50 border-l-4 border-green-500 p-4 rounded-md shadow-sm mb-4 animate-fade-in">
          <div className="flex items-start">
            <div className="flex-shrink-0">
              <svg className="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
              </svg>
            </div>
            <div className="ml-3 flex-1">
              <p className="text-sm font-medium text-green-800">{successMessage}</p>
            </div>
            <button
              onClick={() => setSuccessMessage(null)}
              className="ml-3 flex-shrink-0 text-green-500 hover:text-green-700"
            >
              <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
      )}

      {/* Error Message */}
      {error && (
        <div className="bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm mb-4 animate-fade-in">
          <div className="flex items-start">
            <div className="flex-shrink-0">
              <svg className="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
              </svg>
            </div>
            <div className="ml-3 flex-1">
              <p className="text-sm font-medium text-red-800">{error}</p>
            </div>
            <button
              onClick={() => setError(null)}
              className="ml-3 flex-shrink-0 text-red-500 hover:text-red-700"
            >
              <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
      )}

      <div className="bg-white rounded-lg shadow p-4 sm:p-6 mb-4 sm:mb-6">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
          <input
            type="text"
            placeholder="Search by name or member number..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
          />
          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value as 'active' | 'inactive' | '')}
            className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
          >
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>

      <div className="bg-white rounded-lg shadow overflow-hidden">
        {isLoading ? (
          <div className="p-6 text-center text-gray-500">Loading members...</div>
        ) : members.length === 0 ? (
          <div className="p-6 text-center text-gray-500">No members found</div>
        ) : (
          <>
            {/* Desktop Table View */}
            <div className="hidden md:block overflow-x-auto">
              <table className="w-full">
                <thead className="bg-gray-50 border-b">
                  <tr>
                    <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                      Member Number
                    </th>
                    <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                      Name
                    </th>
                    <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                      Phone
                    </th>
                    <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                      Status
                    </th>
                    <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {members.map((member) => (
                    <tr key={member.id} className="border-b hover:bg-gray-50">
                      <td className="px-6 py-4 text-sm font-medium text-gray-900">
                        {member.member_number}
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-900">
                        {member.first_name} {member.last_name}
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-600">{member.phone}</td>
                      <td className="px-6 py-4 text-sm">
                        <span
                          className={`px-2 py-1 rounded text-xs font-medium ${
                            member.status === 'active'
                              ? 'bg-green-100 text-green-800'
                              : member.status === 'inactive'
                              ? 'bg-yellow-100 text-yellow-800'
                              : 'bg-gray-100 text-gray-800'
                          }`}
                        >
                          {member.status === 'inactive' ? 'Pending Approval' : member.status}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-sm">
                        {member.status === 'inactive' ? (
                          <>
                            <button
                              onClick={() => openApproveDialog(member)}
                              className="text-green-600 hover:text-green-800 mr-3 font-medium"
                            >
                              Approve
                            </button>
                            <button
                              onClick={() => openRejectDialog(member)}
                              className="text-red-600 hover:text-red-800 mr-3 font-medium"
                            >
                              Reject
                            </button>
                            <button
                              onClick={() => fetchMemberProfile(member.id)}
                              className="text-blue-600 hover:text-blue-800"
                            >
                              View Details
                            </button>
                          </>
                        ) : (
                          <div className="flex flex-col gap-1">
                            <div className="flex gap-2">
                              <button
                                onClick={() => fetchMemberProfile(member.id)}
                                className="text-green-600 hover:text-green-800"
                              >
                                View
                              </button>
                              <button
                                onClick={() => openEditModal(member)}
                                className="text-blue-600 hover:text-blue-800"
                              >
                                Edit
                              </button>
                              <button
                                onClick={() => openDeleteDialog(member)}
                                className="text-red-600 hover:text-red-800"
                              >
                                Delete
                              </button>
                            </div>
                            {!member.has_savings_account && (
                              <button
                                onClick={() => handleCreateSavingsAccount(member.id)}
                                disabled={creatingAccountFor === member.id}
                                className="text-xs text-purple-600 hover:text-purple-800 font-medium disabled:opacity-50"
                              >
                                {creatingAccountFor === member.id ? 'Creating...' : '+ Create Savings Account'}
                              </button>
                            )}
                          </div>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Mobile Card View */}
            <div className="md:hidden divide-y divide-gray-200">
              {members.map((member) => (
                <div key={member.id} className="p-4 hover:bg-gray-50">
                  <div className="flex items-start justify-between mb-3">
                    <div className="flex-1 min-w-0">
                      <h3 className="text-sm font-semibold text-gray-900 truncate">
                        {member.first_name} {member.last_name}
                      </h3>
                      <p className="text-xs text-gray-600 mt-1">{member.member_number}</p>
                      <p className="text-xs text-gray-600 mt-1">{member.phone}</p>
                    </div>
                    <span
                      className={`px-2 py-1 rounded text-xs font-medium flex-shrink-0 ml-2 ${
                        member.status === 'active'
                          ? 'bg-green-100 text-green-800'
                          : member.status === 'inactive'
                          ? 'bg-yellow-100 text-yellow-800'
                          : 'bg-gray-100 text-gray-800'
                      }`}
                    >
                      {member.status === 'inactive' ? 'Pending' : member.status}
                    </span>
                  </div>
                  <div className="flex flex-wrap gap-2">
                    {member.status === 'inactive' ? (
                      <>
                        <button
                          onClick={() => openApproveDialog(member)}
                          className="flex-1 min-w-[100px] px-3 py-1.5 bg-green-600 text-white text-xs rounded hover:bg-green-700"
                        >
                          Approve
                        </button>
                        <button
                          onClick={() => openRejectDialog(member)}
                          className="flex-1 min-w-[100px] px-3 py-1.5 bg-red-600 text-white text-xs rounded hover:bg-red-700"
                        >
                          Reject
                        </button>
                        <button
                          onClick={() => fetchMemberProfile(member.id)}
                          className="flex-1 min-w-[100px] px-3 py-1.5 bg-blue-600 text-white text-xs rounded hover:bg-blue-700"
                        >
                          View
                        </button>
                      </>
                    ) : (
                      <>
                        <button
                          onClick={() => fetchMemberProfile(member.id)}
                          className="flex-1 min-w-[80px] px-3 py-1.5 bg-green-600 text-white text-xs rounded hover:bg-green-700"
                        >
                          View
                        </button>
                        <button
                          onClick={() => openEditModal(member)}
                          className="flex-1 min-w-[80px] px-3 py-1.5 bg-blue-600 text-white text-xs rounded hover:bg-blue-700"
                        >
                          Edit
                        </button>
                        <button
                          onClick={() => openDeleteDialog(member)}
                          className="flex-1 min-w-[80px] px-3 py-1.5 bg-red-600 text-white text-xs rounded hover:bg-red-700"
                        >
                          Delete
                        </button>
                        {!member.has_savings_account && (
                          <button
                            onClick={() => handleCreateSavingsAccount(member.id)}
                            disabled={creatingAccountFor === member.id}
                            className="w-full px-3 py-1.5 bg-purple-600 text-white text-xs rounded hover:bg-purple-700 disabled:opacity-50"
                          >
                            {creatingAccountFor === member.id ? 'Creating...' : '+ Create Savings Account'}
                          </button>
                        )}
                      </>
                    )}
                  </div>
                </div>
              ))}
            </div>

            {totalPages > 1 && (
              <div className="px-6 py-4 border-t flex justify-between items-center">
                <button
                  onClick={() => fetchMembers(currentPage - 1)}
                  disabled={currentPage === 1}
                  className="px-3 py-1 border border-gray-300 rounded disabled:opacity-50"
                >
                  Previous
                </button>
                <span className="text-sm text-gray-600">
                  Page {currentPage} of {totalPages}
                </span>
                <button
                  onClick={() => fetchMembers(currentPage + 1)}
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

      <Modal
        isOpen={isCreateModalOpen}
        title="Register New Member"
        onClose={() => setIsCreateModalOpen(false)}
        size="xl"
      >
        <MemberForm
          onSubmit={handleCreateMember}
          onCancel={() => setIsCreateModalOpen(false)}
        />
      </Modal>

      <Modal
        isOpen={isEditModalOpen}
        title="Edit Member"
        onClose={() => {
          setIsEditModalOpen(false);
          setSelectedMember(null);
        }}
        size="md"
      >
        {selectedMember && (
          <MemberForm
            initialData={selectedMember}
            onSubmit={handleEditMember}
            onCancel={() => {
              setIsEditModalOpen(false);
              setSelectedMember(null);
            }}
            isEdit
          />
        )}
      </Modal>

      <Modal
        isOpen={isProfileModalOpen}
        title="Member Profile"
        onClose={() => {
          setIsProfileModalOpen(false);
          setSelectedMember(null);
        }}
        size="xl"
      >
        {selectedMember && (
          <MemberProfileView
            member={selectedMember}
            onApprove={() => {
              setIsProfileModalOpen(false);
              openApproveDialog(selectedMember);
            }}
            onReject={() => {
              setIsProfileModalOpen(false);
              openRejectDialog(selectedMember);
            }}
            onEdit={() => {
              setIsProfileModalOpen(false);
              openEditModal(selectedMember);
            }}
            onClose={() => setIsProfileModalOpen(false)}
          />
        )}
      </Modal>

      <ConfirmDialog
        isOpen={isDeleteDialogOpen}
        title="Delete Member"
        message={`Are you sure you want to delete ${memberToDelete?.first_name} ${memberToDelete?.last_name}? This action cannot be undone.`}
        onConfirm={handleDeleteMember}
        onCancel={() => {
          setIsDeleteDialogOpen(false);
          setMemberToDelete(null);
        }}
        isLoading={isDeleting}
        confirmText="Delete"
        isDangerous
      />

      <ConfirmDialog
        isOpen={isApproveDialogOpen}
        title="Approve Member"
        message={`Are you sure you want to approve ${memberToApprove?.first_name} ${memberToApprove?.last_name}? This will activate their account and create a savings account for them.`}
        onConfirm={handleApproveMember}
        onCancel={() => {
          setIsApproveDialogOpen(false);
          setMemberToApprove(null);
        }}
        isLoading={isApproving}
        confirmText="Approve"
      />

      <Modal
        isOpen={isRejectDialogOpen}
        title="Reject Member Application"
        onClose={() => {
          setIsRejectDialogOpen(false);
          setMemberToReject(null);
          setRejectionReason('');
        }}
        size="md"
      >
        <div className="space-y-4">
          <p className="text-gray-700">
            Are you sure you want to reject the application of{' '}
            <strong>
              {memberToReject?.first_name} {memberToReject?.last_name}
            </strong>
            ? This will permanently delete their application.
          </p>
          
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Rejection Reason (Optional)
            </label>
            <textarea
              value={rejectionReason}
              onChange={(e) => setRejectionReason(e.target.value)}
              rows={4}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
              placeholder="Enter reason for rejection..."
            />
          </div>

          <div className="flex justify-end gap-3 pt-4">
            <button
              onClick={() => {
                setIsRejectDialogOpen(false);
                setMemberToReject(null);
                setRejectionReason('');
              }}
              className="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300"
              disabled={isRejecting}
            >
              Cancel
            </button>
            <button
              onClick={handleRejectMember}
              className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50"
              disabled={isRejecting}
            >
              {isRejecting ? 'Rejecting...' : 'Reject Application'}
            </button>
          </div>
        </div>
      </Modal>
    </div>
  );
};

import React, { useState } from 'react';
import { Member } from '../types';
import { ApplicationPreviewModal } from './ApplicationPreviewModal';

interface MemberWithSummary extends Member {
  financial_summary?: {
    savings_balance: number;
    active_loans: number;
  };
  created_at?: string;
}

interface MemberProfileViewProps {
  member: MemberWithSummary;
  onApprove?: () => void;
  onReject?: () => void;
  onEdit?: () => void;
  onClose: () => void;
}

export const MemberProfileView: React.FC<MemberProfileViewProps> = ({
  member,
  onApprove,
  onReject,
  onEdit,
  onClose,
}) => {
  const [showPreviewModal, setShowPreviewModal] = useState(false);

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-UG', {
      style: 'currency',
      currency: 'UGX',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(value);
  };

  return (
    <div className="max-h-[75vh] overflow-y-auto px-1">
      {/* Header Card with Member Photo and Key Info */}
      <div className="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-xl p-4 sm:p-6 mb-4 sm:mb-6 text-white">
        <div className="flex flex-col sm:flex-row items-start gap-4 sm:gap-6">
          {/* Passport Photo or Initials */}
          <div className="mx-auto sm:mx-0 flex-shrink-0">
            {member.passport_photo ? (
              <img
                src={`http://localhost:8000/storage/${member.passport_photo}`}
                alt={`${member.first_name} ${member.last_name}`}
                className="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover border-4 border-white/30 shadow-lg"
                onError={(e) => {
                  // Fallback to initials if image fails to load
                  e.currentTarget.style.display = 'none';
                  const fallback = e.currentTarget.nextElementSibling as HTMLElement;
                  if (fallback) fallback.style.display = 'flex';
                }}
              />
            ) : null}
            <div 
              className={`w-20 h-20 sm:w-24 sm:h-24 bg-white/20 rounded-full flex items-center justify-center text-3xl sm:text-4xl font-bold backdrop-blur-sm ${member.passport_photo ? 'hidden' : ''}`}
            >
              {member.first_name.charAt(0)}{member.last_name.charAt(0)}
            </div>
          </div>
          
          <div className="flex-1 text-center sm:text-left w-full">
            <h2 className="text-xl sm:text-2xl font-bold mb-2">
              {member.first_name} {member.last_name}
            </h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 text-sm">
              <div>
                <p className="text-indigo-200">Member Number</p>
                <p className="font-semibold text-base sm:text-lg">{member.member_number}</p>
              </div>
              <div>
                <p className="text-indigo-200">Member Since</p>
                <p className="font-semibold">{new Date(member.created_at || Date.now()).toLocaleDateString('en-US', { year: 'numeric', month: 'long' })}</p>
              </div>
            </div>
          </div>
          <div className="mx-auto sm:mx-0">
            <span
              className={`px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-semibold ${
                member.status === 'active'
                  ? 'bg-green-400 text-green-900'
                  : member.status === 'inactive'
                  ? 'bg-yellow-400 text-yellow-900'
                  : 'bg-gray-400 text-gray-900'
              }`}
            >
              {member.status === 'inactive' ? 'Pending Approval' : member.status.toUpperCase()}
            </span>
          </div>
        </div>
      </div>

      {/* Financial Summary Cards - Only for Active Members */}
      {member.financial_summary && member.status === 'active' && (
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-4 sm:mb-6">
          <div className="bg-gradient-to-br from-blue-50 to-blue-100 p-4 sm:p-5 rounded-xl border border-blue-200">
            <div className="flex items-center justify-between mb-2">
              <p className="text-xs sm:text-sm font-medium text-blue-700">Savings Balance</p>
              <svg className="w-6 h-6 sm:w-8 sm:h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <p className="text-2xl sm:text-3xl font-bold text-blue-900">
              {formatCurrency(member.financial_summary.savings_balance)}
            </p>
          </div>
          <div className="bg-gradient-to-br from-green-50 to-green-100 p-4 sm:p-5 rounded-xl border border-green-200">
            <div className="flex items-center justify-between mb-2">
              <p className="text-xs sm:text-sm font-medium text-green-700">Active Loans</p>
              <svg className="w-6 h-6 sm:w-8 sm:h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <p className="text-2xl sm:text-3xl font-bold text-green-900">
              {member.financial_summary.active_loans}
            </p>
          </div>
          <div className="bg-gradient-to-br from-purple-50 to-purple-100 p-4 sm:p-5 rounded-xl border border-purple-200">
            <div className="flex items-center justify-between mb-2">
              <p className="text-xs sm:text-sm font-medium text-purple-700">Account Status</p>
              <svg className="w-6 h-6 sm:w-8 sm:h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <p className="text-xl sm:text-2xl font-bold text-purple-900 capitalize">
              {member.status}
            </p>
          </div>
        </div>
      )}

      {/* Personal Information Section */}
      <div className="bg-white border border-gray-200 rounded-xl p-4 sm:p-6 mb-4">
        <div className="flex items-center gap-2 mb-4">
          <svg className="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          <h3 className="text-base sm:text-lg font-bold text-gray-900">Personal Information</h3>
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
          <div>
            <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Full Name</p>
            <p className="text-sm font-semibold text-gray-900">{member.first_name} {member.last_name}</p>
          </div>
          <div>
            <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Email Address</p>
            <p className="text-sm font-semibold text-gray-900">{member.email}</p>
          </div>
          <div>
            <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Phone Number</p>
            <p className="text-sm font-semibold text-gray-900">{member.phone}</p>
          </div>
          <div>
            <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">National ID</p>
            <p className="text-sm font-semibold text-gray-900">{member.national_id}</p>
          </div>
          {member.employment_info && typeof member.employment_info === 'object' && (
            <>
              {(member.employment_info as any).nationality && (
                <div>
                  <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Nationality</p>
                  <p className="text-sm font-semibold text-gray-900">{(member.employment_info as any).nationality}</p>
                </div>
              )}
              {(member.employment_info as any).marital_status && (
                <div>
                  <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Marital Status</p>
                  <p className="text-sm font-semibold text-gray-900 capitalize">{(member.employment_info as any).marital_status}</p>
                </div>
              )}
              {(member.employment_info as any).village && (
                <div>
                  <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Village</p>
                  <p className="text-sm font-semibold text-gray-900">{(member.employment_info as any).village}</p>
                </div>
              )}
              {(member.employment_info as any).district && (
                <div>
                  <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">District</p>
                  <p className="text-sm font-semibold text-gray-900">{(member.employment_info as any).district}</p>
                </div>
              )}
            </>
          )}
          <div className="sm:col-span-2 lg:col-span-3">
            <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Address</p>
            <p className="text-sm font-semibold text-gray-900">{member.address}</p>
          </div>
        </div>
      </div>

      {/* Employment Information Section */}
      {member.employment_info && typeof member.employment_info === 'object' && (
        <>
          {((member.employment_info as any).occupation || (member.employment_info as any).source_of_income) && (
            <div className="bg-white border border-gray-200 rounded-xl p-4 sm:p-6 mb-4">
              <div className="flex items-center gap-2 mb-4">
                <svg className="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <h3 className="text-base sm:text-lg font-bold text-gray-900">Employment Details</h3>
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                {(member.employment_info as any).occupation && (
                  <div>
                    <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Occupation</p>
                    <p className="text-sm font-semibold text-gray-900">{(member.employment_info as any).occupation}</p>
                  </div>
                )}
                {(member.employment_info as any).source_of_income && (
                  <div>
                    <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Source of Income</p>
                    <p className="text-sm font-semibold text-gray-900 capitalize">{(member.employment_info as any).source_of_income}</p>
                  </div>
                )}
                {(member.employment_info as any).organization && (
                  <div>
                    <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Organization</p>
                    <p className="text-sm font-semibold text-gray-900">{(member.employment_info as any).organization}</p>
                  </div>
                )}
                {(member.employment_info as any).job_title && (
                  <div>
                    <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Job Title</p>
                    <p className="text-sm font-semibold text-gray-900">{(member.employment_info as any).job_title}</p>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Next of Kin Section */}
          {(member.employment_info as any).next_of_kin && (
            <div className="bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-200 rounded-xl p-4 sm:p-6 mb-4">
              <div className="flex items-center gap-2 mb-4">
                <svg className="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h3 className="text-base sm:text-lg font-bold text-gray-900">Next of Kin</h3>
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                <div>
                  <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Name</p>
                  <p className="text-sm font-semibold text-gray-900">{(member.employment_info as any).next_of_kin.name}</p>
                </div>
                <div>
                  <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Relationship</p>
                  <p className="text-sm font-semibold text-gray-900 capitalize">{(member.employment_info as any).next_of_kin.relationship}</p>
                </div>
                <div>
                  <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Contact</p>
                  <p className="text-sm font-semibold text-gray-900">{(member.employment_info as any).next_of_kin.contact}</p>
                </div>
                <div>
                  <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Residence</p>
                  <p className="text-sm font-semibold text-gray-900">{(member.employment_info as any).next_of_kin.residence}</p>
                </div>
              </div>
            </div>
          )}

          {/* Bank Details Section */}
          {(member.employment_info as any).bank_details && (
            <div className="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-4 sm:p-6 mb-4">
              <div className="flex items-center gap-2 mb-4">
                <svg className="w-5 h-5 sm:w-6 sm:h-6 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                <h3 className="text-base sm:text-lg font-bold text-gray-900">Bank Details</h3>
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                <div>
                  <p className="text-xs font-medium text-blue-700 uppercase tracking-wide mb-1">Account Name</p>
                  <p className="text-sm font-semibold text-gray-900">{(member.employment_info as any).bank_details.account_name}</p>
                </div>
                <div>
                  <p className="text-xs font-medium text-blue-700 uppercase tracking-wide mb-1">Account Number</p>
                  <p className="text-sm font-semibold text-gray-900 font-mono">{(member.employment_info as any).bank_details.account_number}</p>
                </div>
                <div>
                  <p className="text-xs font-medium text-blue-700 uppercase tracking-wide mb-1">Bank Name</p>
                  <p className="text-sm font-semibold text-gray-900">{(member.employment_info as any).bank_details.bank_name}</p>
                </div>
                <div>
                  <p className="text-xs font-medium text-blue-700 uppercase tracking-wide mb-1">Branch Location</p>
                  <p className="text-sm font-semibold text-gray-900">{(member.employment_info as any).bank_details.bank_location}</p>
                </div>
              </div>
            </div>
          )}

          {/* Referee Section */}
          {(member.employment_info as any).referee && (
            <div className="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-4 sm:p-6 mb-4">
              <div className="flex items-center gap-2 mb-4">
                <svg className="w-5 h-5 sm:w-6 sm:h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <h3 className="text-base sm:text-lg font-bold text-gray-900">Referee/Nominee</h3>
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <div>
                  <p className="text-xs font-medium text-green-700 uppercase tracking-wide mb-1">Name</p>
                  <p className="text-sm font-semibold text-gray-900">{(member.employment_info as any).referee.name}</p>
                </div>
                <div>
                  <p className="text-xs font-medium text-green-700 uppercase tracking-wide mb-1">Member Number</p>
                  <p className="text-sm font-semibold text-gray-900 font-mono">{(member.employment_info as any).referee.member_number}</p>
                </div>
                <div>
                  <p className="text-xs font-medium text-green-700 uppercase tracking-wide mb-1">Contact</p>
                  <p className="text-sm font-semibold text-gray-900">{(member.employment_info as any).referee.contact}</p>
                </div>
              </div>
            </div>
          )}

          {/* Fees & Savings Section */}
          {(member.employment_info as any).fees && (
            <div className="bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-xl p-6 mb-4">
              <div className="flex items-center gap-2 mb-4">
                <svg className="w-6 h-6 text-yellow-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 className="text-lg font-bold text-gray-900">Fees & Monthly Savings</h3>
              </div>
              <div className="grid grid-cols-3 gap-6">
                <div>
                  <p className="text-xs font-medium text-yellow-700 uppercase tracking-wide mb-1">Entrance Fee</p>
                  <p className="text-sm font-bold text-gray-900">UGX {(member.employment_info as any).fees.entrance_fee?.toLocaleString()}</p>
                </div>
                <div>
                  <p className="text-xs font-medium text-yellow-700 uppercase tracking-wide mb-1">Passbook Fee</p>
                  <p className="text-sm font-bold text-gray-900">UGX {(member.employment_info as any).fees.passbook_fee?.toLocaleString()}</p>
                </div>
                <div>
                  <p className="text-xs font-medium text-yellow-700 uppercase tracking-wide mb-1">Monthly Savings</p>
                  <p className="text-sm font-bold text-gray-900">UGX {(member.employment_info as any).fees.monthly_savings?.toLocaleString()}</p>
                </div>
              </div>
            </div>
          )}
        </>
      )}

      {/* Action Buttons */}
      <div className="flex flex-col sm:flex-row justify-between gap-3 mt-6 pt-6 border-t">
        {/* Print/Download Buttons */}
        <div className="flex flex-col sm:flex-row gap-2">
          <button
            onClick={() => setShowPreviewModal(true)}
            className="px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium flex items-center justify-center gap-2 text-sm"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            Preview Application
          </button>
        </div>

        {/* Status Action Buttons */}
        <div className="flex flex-col sm:flex-row gap-2">
          {member.status === 'inactive' ? (
            <>
              {onApprove && (
                <button
                  onClick={onApprove}
                  className="px-4 sm:px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium flex items-center justify-center gap-2"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Approve
                </button>
              )}
              {onReject && (
                <button
                  onClick={onReject}
                  className="px-4 sm:px-6 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium flex items-center justify-center gap-2"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Reject
                </button>
              )}
            </>
          ) : (
            <>
              {onEdit && (
                <button
                  onClick={onEdit}
                  className="px-4 sm:px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium flex items-center justify-center gap-2"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                  Edit
                </button>
              )}
            </>
          )}
          <button
            onClick={onClose}
            className="px-4 sm:px-6 py-2.5 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors font-medium"
          >
            Close
          </button>
        </div>
      </div>

      {/* Application Preview Modal */}
      <ApplicationPreviewModal
        member={member}
        isOpen={showPreviewModal}
        onClose={() => setShowPreviewModal(false)}
      />
    </div>
  );
};

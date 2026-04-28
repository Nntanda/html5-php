import React from 'react';

interface MemberApplicationPrintProps {
  member: any;
}

export const MemberApplicationPrint: React.FC<MemberApplicationPrintProps> = ({ member }) => {
  const employmentInfo = typeof member.employment_info === 'string' 
    ? JSON.parse(member.employment_info) 
    : member.employment_info || {};

  return (
    <div className="print-content bg-white p-8 max-w-4xl mx-auto" style={{ fontFamily: 'Arial, sans-serif' }}>
      {/* Header */}
      <div className="text-center mb-6 border-b-2 border-gray-800 pb-4">
        <h1 className="text-2xl font-bold text-gray-900 mb-1">KITOVU HOSPITAL STAFF SAVING SCHEME</h1>
        <p className="text-sm text-gray-600">Membership Application Form</p>
      </div>

      {/* Application Info Bar */}
      <div className="bg-gray-100 p-3 rounded mb-6 flex justify-between items-center">
        <div>
          <p className="text-xs text-gray-600">Application Date</p>
          <p className="font-semibold">{new Date(member.created_at || Date.now()).toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
          })}</p>
        </div>
        <div>
          <p className="text-xs text-gray-600">Member Number</p>
          <p className="font-bold text-lg">{member.member_number}</p>
        </div>
        <div>
          <p className="text-xs text-gray-600">Status</p>
          <p className={`font-semibold ${member.status === 'active' ? 'text-green-600' : 'text-yellow-600'}`}>
            {member.status === 'inactive' ? 'PENDING APPROVAL' : member.status.toUpperCase()}
          </p>
        </div>
      </div>

      <div className="grid grid-cols-12 gap-6">
        {/* Main Content - 9 columns */}
        <div className="col-span-9">
          {/* Section 1: Personal Information */}
          <div className="mb-6">
            <h2 className="text-lg font-bold bg-gray-800 text-white px-3 py-2 mb-3">1. PERSONAL INFORMATION</h2>
            <div className="grid grid-cols-2 gap-x-6 gap-y-3">
              <div>
                <p className="text-xs text-gray-600 font-semibold">Full Name</p>
                <p className="text-sm border-b border-gray-300 pb-1">{member.first_name} {member.last_name}</p>
              </div>
              <div>
                <p className="text-xs text-gray-600 font-semibold">Gender</p>
                <p className="text-sm border-b border-gray-300 pb-1 capitalize">{employmentInfo.gender || 'N/A'}</p>
              </div>
              <div>
                <p className="text-xs text-gray-600 font-semibold">Marital Status</p>
                <p className="text-sm border-b border-gray-300 pb-1 capitalize">{employmentInfo.marital_status || 'N/A'}</p>
              </div>
              <div>
                <p className="text-xs text-gray-600 font-semibold">Nationality</p>
                <p className="text-sm border-b border-gray-300 pb-1">{employmentInfo.nationality || 'N/A'}</p>
              </div>
              <div>
                <p className="text-xs text-gray-600 font-semibold">Village</p>
                <p className="text-sm border-b border-gray-300 pb-1">{employmentInfo.village || 'N/A'}</p>
              </div>
              <div>
                <p className="text-xs text-gray-600 font-semibold">District</p>
                <p className="text-sm border-b border-gray-300 pb-1">{employmentInfo.district || 'N/A'}</p>
              </div>
              <div>
                <p className="text-xs text-gray-600 font-semibold">Mobile Contact</p>
                <p className="text-sm border-b border-gray-300 pb-1">{member.phone}</p>
              </div>
              <div>
                <p className="text-xs text-gray-600 font-semibold">Email Address</p>
                <p className="text-sm border-b border-gray-300 pb-1">{member.email}</p>
              </div>
              <div className="col-span-2">
                <p className="text-xs text-gray-600 font-semibold">National ID Number</p>
                <p className="text-sm border-b border-gray-300 pb-1">{member.national_id || 'N/A'}</p>
              </div>
              <div className="col-span-2">
                <p className="text-xs text-gray-600 font-semibold">Address</p>
                <p className="text-sm border-b border-gray-300 pb-1">{member.address}</p>
              </div>
            </div>
          </div>

          {/* Section 2: Next of Kin */}
          {employmentInfo.next_of_kin && (
            <div className="mb-6">
              <h2 className="text-lg font-bold bg-gray-800 text-white px-3 py-2 mb-3">2. NEXT OF KIN DETAILS</h2>
              <div className="grid grid-cols-2 gap-x-6 gap-y-3">
                <div>
                  <p className="text-xs text-gray-600 font-semibold">Name</p>
                  <p className="text-sm border-b border-gray-300 pb-1">{employmentInfo.next_of_kin.name}</p>
                </div>
                <div>
                  <p className="text-xs text-gray-600 font-semibold">Relationship</p>
                  <p className="text-sm border-b border-gray-300 pb-1 capitalize">{employmentInfo.next_of_kin.relationship}</p>
                </div>
                <div>
                  <p className="text-xs text-gray-600 font-semibold">Contact</p>
                  <p className="text-sm border-b border-gray-300 pb-1">{employmentInfo.next_of_kin.contact}</p>
                </div>
                <div>
                  <p className="text-xs text-gray-600 font-semibold">Residence</p>
                  <p className="text-sm border-b border-gray-300 pb-1">{employmentInfo.next_of_kin.residence}</p>
                </div>
              </div>
            </div>
          )}

          {/* Section 3: Employment Details */}
          <div className="mb-6">
            <h2 className="text-lg font-bold bg-gray-800 text-white px-3 py-2 mb-3">3. EMPLOYMENT DETAILS</h2>
            <div className="grid grid-cols-2 gap-x-6 gap-y-3">
              <div>
                <p className="text-xs text-gray-600 font-semibold">Occupation</p>
                <p className="text-sm border-b border-gray-300 pb-1">{employmentInfo.occupation || 'N/A'}</p>
              </div>
              <div>
                <p className="text-xs text-gray-600 font-semibold">Source of Income</p>
                <p className="text-sm border-b border-gray-300 pb-1 capitalize">{employmentInfo.source_of_income || 'N/A'}</p>
              </div>
              {employmentInfo.organization && (
                <div>
                  <p className="text-xs text-gray-600 font-semibold">Organization</p>
                  <p className="text-sm border-b border-gray-300 pb-1">{employmentInfo.organization}</p>
                </div>
              )}
              {employmentInfo.job_title && (
                <div>
                  <p className="text-xs text-gray-600 font-semibold">Job Title</p>
                  <p className="text-sm border-b border-gray-300 pb-1">{employmentInfo.job_title}</p>
                </div>
              )}
            </div>
          </div>

          {/* Section 4: Bank Details */}
          {employmentInfo.bank_details && (
            <div className="mb-6">
              <h2 className="text-lg font-bold bg-gray-800 text-white px-3 py-2 mb-3">4. BANK DETAILS</h2>
              <div className="grid grid-cols-2 gap-x-6 gap-y-3">
                <div>
                  <p className="text-xs text-gray-600 font-semibold">Account Name</p>
                  <p className="text-sm border-b border-gray-300 pb-1">{employmentInfo.bank_details.account_name}</p>
                </div>
                <div>
                  <p className="text-xs text-gray-600 font-semibold">Account Number</p>
                  <p className="text-sm border-b border-gray-300 pb-1 font-mono">{employmentInfo.bank_details.account_number}</p>
                </div>
                <div>
                  <p className="text-xs text-gray-600 font-semibold">Bank Name</p>
                  <p className="text-sm border-b border-gray-300 pb-1">{employmentInfo.bank_details.bank_name}</p>
                </div>
                <div>
                  <p className="text-xs text-gray-600 font-semibold">Branch Location</p>
                  <p className="text-sm border-b border-gray-300 pb-1">{employmentInfo.bank_details.bank_location}</p>
                </div>
              </div>
            </div>
          )}

          {/* Section 5: Fees & Savings */}
          {employmentInfo.fees && (
            <div className="mb-6">
              <h2 className="text-lg font-bold bg-gray-800 text-white px-3 py-2 mb-3">5. FEES & MONTHLY SAVINGS</h2>
              <div className="grid grid-cols-3 gap-x-6 gap-y-3">
                <div>
                  <p className="text-xs text-gray-600 font-semibold">Entrance Fee</p>
                  <p className="text-sm border-b border-gray-300 pb-1">UGX {employmentInfo.fees.entrance_fee?.toLocaleString()}</p>
                </div>
                <div>
                  <p className="text-xs text-gray-600 font-semibold">Passbook Fee</p>
                  <p className="text-sm border-b border-gray-300 pb-1">UGX {employmentInfo.fees.passbook_fee?.toLocaleString()}</p>
                </div>
                <div>
                  <p className="text-xs text-gray-600 font-semibold">Monthly Savings</p>
                  <p className="text-sm border-b border-gray-300 pb-1 font-bold">UGX {employmentInfo.fees.monthly_savings?.toLocaleString()}</p>
                </div>
                {employmentInfo.fees.monthly_savings_words && (
                  <div className="col-span-3">
                    <p className="text-xs text-gray-600 font-semibold">Amount in Words</p>
                    <p className="text-sm border-b border-gray-300 pb-1 italic">{employmentInfo.fees.monthly_savings_words}</p>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Section 6: Referee */}
          {employmentInfo.referee && (
            <div className="mb-6">
              <h2 className="text-lg font-bold bg-gray-800 text-white px-3 py-2 mb-3">6. REFEREE/NOMINEE</h2>
              <div className="grid grid-cols-3 gap-x-6 gap-y-3">
                <div>
                  <p className="text-xs text-gray-600 font-semibold">Name</p>
                  <p className="text-sm border-b border-gray-300 pb-1">{employmentInfo.referee.name}</p>
                </div>
                <div>
                  <p className="text-xs text-gray-600 font-semibold">Member Number</p>
                  <p className="text-sm border-b border-gray-300 pb-1 font-mono">{employmentInfo.referee.member_number}</p>
                </div>
                <div>
                  <p className="text-xs text-gray-600 font-semibold">Contact</p>
                  <p className="text-sm border-b border-gray-300 pb-1">{employmentInfo.referee.contact}</p>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Sidebar - 3 columns */}
        <div className="col-span-3">
          {/* Passport Photo */}
          <div className="border-2 border-gray-800 p-2 mb-4">
            {member.passport_photo ? (
              <img
                src={`http://localhost:8000/storage/${member.passport_photo}`}
                alt="Passport"
                className="w-full h-48 object-cover"
              />
            ) : (
              <div className="w-full h-48 bg-gray-200 flex items-center justify-center">
                <div className="text-center">
                  <div className="text-4xl font-bold text-gray-400 mb-2">
                    {member.first_name.charAt(0)}{member.last_name.charAt(0)}
                  </div>
                  <p className="text-xs text-gray-500">No Photo</p>
                </div>
              </div>
            )}
            <p className="text-xs text-center mt-2 font-semibold">PASSPORT PHOTO</p>
          </div>

          {/* Signature Box */}
          <div className="border-2 border-gray-300 p-3 mb-4">
            <p className="text-xs font-semibold mb-2">APPLICANT'S SIGNATURE</p>
            <div className="border-b-2 border-gray-400 h-16 mb-2"></div>
            <p className="text-xs text-gray-600">Date: _______________</p>
          </div>

          {/* Approval Section */}
          <div className="border-2 border-gray-300 p-3">
            <p className="text-xs font-semibold mb-2 bg-gray-200 px-2 py-1">FOR OFFICIAL USE ONLY</p>
            <div className="space-y-3">
              <div>
                <p className="text-xs font-semibold">Approved By:</p>
                <div className="border-b border-gray-400 h-8"></div>
              </div>
              <div>
                <p className="text-xs font-semibold">Signature:</p>
                <div className="border-b border-gray-400 h-8"></div>
              </div>
              <div>
                <p className="text-xs font-semibold">Date:</p>
                <div className="border-b border-gray-400 h-6"></div>
              </div>
              <div>
                <p className="text-xs font-semibold">Stamp:</p>
                <div className="border-2 border-dashed border-gray-400 h-16"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Declaration */}
      <div className="mt-6 border-t-2 border-gray-300 pt-4">
        <h3 className="text-sm font-bold mb-2">DECLARATION</h3>
        <p className="text-xs text-gray-700 leading-relaxed">
          I, <strong>{member.first_name} {member.last_name}</strong>, hereby declare that the information provided in this application form is true, accurate, and complete to the best of my knowledge. I understand that any false information may result in the rejection of my application or termination of my membership. I agree to abide by the Kitovu Hospital Staff Saving Scheme's bylaws, rules, and regulations.
        </p>
      </div>

      {/* Footer */}
      <div className="mt-6 text-center text-xs text-gray-500 border-t pt-4">
        <p>This is a computer-generated document. Printed on {new Date().toLocaleDateString('en-US', { 
          year: 'numeric', 
          month: 'long', 
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        })}</p>
        <p className="mt-1">Kitovu Hospital Staff Saving Scheme © {new Date().getFullYear()}</p>
      </div>
    </div>
  );
};

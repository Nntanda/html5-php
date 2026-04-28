pimport React, { useState } from 'react';
import { Member } from '../types';
import { apiClient } from '../api/client';

interface MemberData {
  id: number;
  member_number: string;
  first_name: string;
  last_name: string;
  phone: string;
  email: string;
}

interface RegistrationForm {
  // 1. Personal Details
  fullName: string;
  gender: string;
  maritalStatus: string;
  nationality: string;
  village: string;
  district: string;
  mobileContact: string;
  email: string;
  nationalId: string;
  category: string;
  
  // 2. Next of Kin Details
  nextOfKinName: string;
  nextOfKinResidence: string;
  nextOfKinContact: string;
  nextOfKinRelationship: string;
  
  // 3. Employment Details
  occupation: string;
  sourceOfIncome: string;
  otherSourceOfIncome: string;
  organization: string;
  jobTitle: string;
  
  // 4. Bank Details
  bankAccountName: string;
  bankAccountNumber: string;
  bankName: string;
  bankLocation: string;
  
  // 5. Declaration
  entranceFee: string;
  passbookFee: string;
  monthlySavings: string;
  monthlySavingsWords: string;
  
  // 6. Referee/Nominee
  refereeName: string;
  refereeContact: string;
  refereeMemberNumber: string;
  
  // Account Information
  password: string;
  confirmPassword: string;
  
  // Admin can directly activate
  activateImmediately: boolean;
}

interface MemberFormProps {
  initialData?: Member;
  onSubmit: (data: any) => Promise<void>;
  onCancel: () => void;
  isEdit?: boolean;
}

export const MemberForm: React.FC<MemberFormProps> = ({
  initialData,
  onSubmit,
  onCancel,
  isEdit = false,
}) => {
  const [currentStep, setCurrentStep] = useState(1);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  // Referee search state
  const [refereeSearch, setRefereeSearch] = useState('');
  const [searchingReferee, setSearchingReferee] = useState(false);
  const [refereeResults, setRefereeResults] = useState<MemberData[]>([]);
  const [selectedReferee, setSelectedReferee] = useState<MemberData | null>(null);
  const [showRefereeDropdown, setShowRefereeDropdown] = useState(false);

  const [formData, setFormData] = useState<RegistrationForm>({
    fullName: initialData ? `${initialData.first_name} ${initialData.last_name}` : '',
    gender: '',
    maritalStatus: '',
    nationality: 'Ugandan',
    village: '',
    district: '',
    mobileContact: initialData?.phone || '',
    email: initialData?.email || '',
    nationalId: initialData?.national_id || '',
    category: '',
    nextOfKinName: '',
    nextOfKinResidence: '',
    nextOfKinContact: '',
    nextOfKinRelationship: '',
    occupation: '',
    sourceOfIncome: '',
    otherSourceOfIncome: '',
    organization: '',
    jobTitle: '',
    bankAccountName: '',
    bankAccountNumber: '',
    bankName: '',
    bankLocation: '',
    entranceFee: '10000',
    passbookFee: '5000',
    monthlySavings: '',
    monthlySavingsWords: '',
    refereeName: '',
    refereeContact: '',
    refereeMemberNumber: '',
    password: '',
    confirmPassword: '',
    activateImmediately: true, // Admin can activate immediately
  });

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value, type } = e.target;
    const newValue = type === 'checkbox' ? (e.target as HTMLInputElement).checked : value;
    
    setFormData(prev => ({
      ...prev,
      [name]: newValue
    }));
  };

  // Search for referee members
  const searchReferee = async (searchTerm: string) => {
    if (searchTerm.length < 2) {
      setRefereeResults([]);
      return;
    }

    setSearchingReferee(true);
    try {
      const response = await apiClient.get<{ data: MemberData[] }>(`/members/search?q=${encodeURIComponent(searchTerm)}`);
      setRefereeResults(response.data.data || []);
      setShowRefereeDropdown(true);
    } catch (err) {
      console.error('Failed to search members:', err);
    } finally {
      setSearchingReferee(false);
    }
  };

  const handleRefereeSearch = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    setRefereeSearch(value);
    searchReferee(value);
  };

  const selectReferee = (member: MemberData) => {
    setSelectedReferee(member);
    setRefereeSearch(`${member.first_name} ${member.last_name} (${member.member_number})`);
    setFormData(prev => ({
      ...prev,
      refereeName: `${member.first_name} ${member.last_name}`,
      refereeContact: member.phone,
      refereeMemberNumber: member.member_number,
    }));
    setShowRefereeDropdown(false);
  };

  const validateStep = (step: number): boolean => {
    setError(null);
    
    switch (step) {
      case 1:
        if (!formData.fullName || !formData.gender || !formData.maritalStatus || !formData.nationality || 
            !formData.village || !formData.district || !formData.mobileContact || !formData.email || 
            !formData.nationalId || !formData.category) {
          setError('Please fill in all personal details fields');
          return false;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
          setError('Please enter a valid email address');
          return false;
        }
        break;
      case 2:
        if (!formData.nextOfKinName || !formData.nextOfKinResidence || !formData.nextOfKinContact || !formData.nextOfKinRelationship) {
          setError('Please fill in all next of kin details');
          return false;
        }
        break;
      case 3:
        if (!formData.occupation || !formData.sourceOfIncome) {
          setError('Please fill in employment details');
          return false;
        }
        if (formData.sourceOfIncome === 'other' && !formData.otherSourceOfIncome) {
          setError('Please specify other source of income');
          return false;
        }
        break;
      case 4:
        if (!formData.bankAccountName || !formData.bankAccountNumber || !formData.bankName || !formData.bankLocation) {
          setError('Please fill in all bank details');
          return false;
        }
        break;
      case 5:
        if (!formData.monthlySavings) {
          setError('Please enter monthly savings amount');
          return false;
        }
        break;
      case 6:
        if (!selectedReferee || !formData.refereeMemberNumber) {
          setError('Please select a referee/nominee from existing members');
          return false;
        }
        if (!formData.password || !formData.confirmPassword) {
          setError('Please enter and confirm password');
          return false;
        }
        if (formData.password.length < 8) {
          setError('Password must be at least 8 characters long');
          return false;
        }
        if (formData.password !== formData.confirmPassword) {
          setError('Passwords do not match');
          return false;
        }
        break;
    }
    return true;
  };

  const handleNext = () => {
    if (validateStep(currentStep)) {
      setCurrentStep(prev => prev + 1);
    }
  };

  const handlePrevious = () => {
    setError(null);
    setCurrentStep(prev => prev - 1);
  };

  const handleSubmitForm = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateStep(6)) return;
    
    setIsSubmitting(true);
    setError(null);

    try {
      const submitData = {
        // 1. Personal Details
        full_name: formData.fullName,
        gender: formData.gender,
        marital_status: formData.maritalStatus,
        nationality: formData.nationality,
        village: formData.village,
        district: formData.district,
        mobile_contact: formData.mobileContact,
        email: formData.email,
        national_id: formData.nationalId,
        category: formData.category,
        
        // 2. Next of Kin Details
        next_of_kin_name: formData.nextOfKinName,
        next_of_kin_residence: formData.nextOfKinResidence,
        next_of_kin_contact: formData.nextOfKinContact,
        next_of_kin_relationship: formData.nextOfKinRelationship,
        
        // 3. Employment Details
        occupation: formData.occupation,
        source_of_income: formData.sourceOfIncome,
        other_source_of_income: formData.otherSourceOfIncome,
        organization: formData.organization,
        job_title: formData.jobTitle,
        
        // 4. Bank Details
        bank_account_name: formData.bankAccountName,
        bank_account_number: formData.bankAccountNumber,
        bank_name: formData.bankName,
        bank_location: formData.bankLocation,
        
        // 5. Declaration
        entrance_fee: parseFloat(formData.entranceFee),
        passbook_fee: parseFloat(formData.passbookFee),
        monthly_savings: parseFloat(formData.monthlySavings),
        monthly_savings_words: formData.monthlySavingsWords,
        
        // 6. Referee/Nominee
        referee_name: formData.refereeName,
        referee_contact: formData.refereeContact,
        referee_member_number: formData.refereeMemberNumber,
        
        // Account
        password: formData.password,
        
        // Admin-specific: activate immediately
        activate_immediately: formData.activateImmediately,
      };

      await onSubmit(submitData);
    } catch (err: any) {
      console.error('Registration error:', err);
      
      // Handle specific error types
      if (err.message.includes('email')) {
        setError('This email is already registered. Please use a different email.');
        setCurrentStep(1);
      } else if (err.message.includes('national_id')) {
        setError('This National ID is already registered. Please check the details.');
        setCurrentStep(1);
      } else {
        setError(err.message || 'Registration failed. Please try again.');
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  const renderStepIndicator = () => (
    <div className="flex items-center justify-center mb-6 overflow-x-auto">
      {[1, 2, 3, 4, 5, 6].map((step) => (
        <div key={step} className="flex items-center">
          <div className={`w-8 h-8 rounded-full flex items-center justify-center font-semibold text-xs ${
            step === currentStep 
              ? 'bg-indigo-600 text-white' 
              : step < currentStep 
              ? 'bg-green-500 text-white' 
              : 'bg-gray-200 text-gray-600'
          }`}>
            {step < currentStep ? '✓' : step}
          </div>
          {step < 6 && (
            <div className={`w-8 h-1 ${step < currentStep ? 'bg-green-500' : 'bg-gray-200'}`} />
          )}
        </div>
      ))}
    </div>
  );

  // For edit mode, show simple form
  if (isEdit) {
    return (
      <form onSubmit={async (e) => {
        e.preventDefault();
        setIsSubmitting(true);
        setError(null);
        try {
          await onSubmit({
            first_name: formData.fullName.split(' ')[0],
            last_name: formData.fullName.split(' ').slice(1).join(' '),
            phone: formData.mobileContact,
            address: `${formData.village}, ${formData.district}`,
            status: 'active',
          });
        } catch (err: any) {
          setError(err.message || 'An error occurred');
        } finally {
          setIsSubmitting(false);
        }
      }}>
        {error && (
          <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {error}
          </div>
        )}

        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
            <input
              type="text"
              name="fullName"
              value={formData.fullName}
              onChange={handleInputChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
            <input
              type="tel"
              name="mobileContact"
              value={formData.mobileContact}
              onChange={handleInputChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Village *</label>
            <input
              type="text"
              name="village"
              value={formData.village}
              onChange={handleInputChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">District *</label>
            <input
              type="text"
              name="district"
              value={formData.district}
              onChange={handleInputChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            />
          </div>
        </div>

        <div className="flex justify-end gap-3 mt-6">
          <button
            type="button"
            onClick={onCancel}
            disabled={isSubmitting}
            className="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 disabled:opacity-50"
          >
            Cancel
          </button>
          <button
            type="submit"
            disabled={isSubmitting}
            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
          >
            {isSubmitting ? 'Saving...' : 'Update Member'}
          </button>
        </div>
      </form>
    );
  }

  return (
    <form onSubmit={handleSubmitForm} className="max-h-[70vh] overflow-y-auto px-1">
      {renderStepIndicator()}

      {error && (
        <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg flex items-start gap-2">
          <svg className="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p className="text-sm text-red-800">{error}</p>
        </div>
      )}

      {/* Step 1: Personal Details */}
      {currentStep === 1 && (
        <div className="space-y-4">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">1. Personal Details</h3>
          
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Names in Full *</label>
            <input
              type="text"
              name="fullName"
              value={formData.fullName}
              onChange={handleInputChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              required
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
              <select
                name="gender"
                value={formData.gender}
                onChange={handleInputChange}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                required
              >
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
            </div>
            
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Marital Status *</label>
              <select
                name="maritalStatus"
                value={formData.maritalStatus}
                onChange={handleInputChange}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                required
              >
                <option value="">Select Status</option>
                <option value="single">Single</option>
                <option value="married">Married</option>
                <option value="divorced">Divorced</option>
                <option value="widowed">Widowed</option>
              </select>
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Nationality *</label>
            <input
              type="text"
              name="nationality"
              value={formData.nationality}
              onChange={handleInputChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              required
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Village *</label>
              <input
                type="text"
                name="village"
                value={formData.village}
                onChange={handleInputChange}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                required
              />
            </div>
            
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">District *</label>
              <input
                type="text"
                name="district"
                value={formData.district}
                onChange={handleInputChange}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                required
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Mobile Contact *</label>
            <input
              type="tel"
              name="mobileContact"
              value={formData.mobileContact}
              onChange={handleInputChange}
              placeholder="+256 700 000 000"
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
            <input
              type="email"
              name="email"
              value={formData.email}
              onChange={handleInputChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">National ID No. (NIN) *</label>
            <input
              type="text"
              name="nationalId"
              value={formData.nationalId}
              onChange={handleInputChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Member Category *</label>
            <select
              name="category"
              value={formData.category}
              onChange={handleInputChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              required
            >
              <option value="">Select Category</option>
              <option value="staff">1. Staff</option>
              <option value="act_program">2. ACT-Program</option>
              <option value="nursing_school">3. Nursing School</option>
              <option value="hc_staff">4. HC Staff</option>
              <option value="non_hospital_staff">5. Non Hospital Staff</option>
            </select>
            <p className="text-xs text-gray-500 mt-1">
              Categories 1-4: Savings from salary deductions + optional direct deposits. Category 5: Direct deposits only with evidence required.
            </p>
          </div>
        </div>
      )}


      {/* Step 2: Next of Kin Details */}
      {currentStep === 2 && (
        <div className="space-y-4">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">2. Details of the Next of Kin</h3>
          
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Name *</label>
            <input
              type="text"
              name="nextOfKinName"
              value={formData.nextOfKinName}
              onChange={handleInputChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Residence *</label>
            <input
              type="text"
              name="nextOfKinResidence"
              value={formData.nextOfKinResidence}
              onChange={handleInputChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Contact *</label>
            <input
              type="tel"
              name="nextOfKinContact"
              value={formData.nextOfKinContact}
              onChange={handleInputChange}
              placeholder="+256 700 000 000"
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Relationship to the Next of Kin *</label>
            <select
              name="nextOfKinRelationship"
              value={formData.nextOfKinRelationship}
              onChange={handleInputChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              required
            >
              <option value="">Select Relationship</option>
              <option value="spouse">Spouse</option>
              <option value="parent">Parent</option>
              <option value="child">Child</option>
              <option value="sibling">Sibling</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
      )}

      {/* Step 3: Employment Details */}
      {currentStep === 3 && (
        <div className="space-y-4">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">3. Employment Details</h3>
          
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Occupation *</label>
            <input
              type="text"
              name="occupation"
              value={formData.occupation}
              onChange={handleInputChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Source of Income *</label>
            <select
              name="sourceOfIncome"
              value={formData.sourceOfIncome}
              onChange={handleInputChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              required
            >
              <option value="">Select Source</option>
              <option value="salaried">Salaried</option>
              <option value="self-employed">Self-Employed</option>
              <option value="other">Other</option>
            </select>
          </div>

          {formData.sourceOfIncome === 'other' && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Specify Other Source *</label>
              <input
                type="text"
                name="otherSourceOfIncome"
                value={formData.otherSourceOfIncome}
                onChange={handleInputChange}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                required
              />
            </div>
          )}

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Organization/Department (if employed)</label>
            <input
              type="text"
              name="organization"
              value={formData.organization}
              onChange={handleInputChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Job Title/Designation</label>
            <input
              type="text"
              name="jobTitle"
              value={formData.jobTitle}
              onChange={handleInputChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>
        </div>
      )}

      {/* Step 4: Bank Details */}
      {currentStep === 4 && (
        <div className="space-y-4">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">4. Applicant's Bank Details</h3>
          
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Account Name *</label>
            <input
              type="text"
              name="bankAccountName"
              value={formData.bankAccountName}
              onChange={handleInputChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">A/C No. *</label>
            <input
              type="text"
              name="bankAccountNumber"
              value={formData.bankAccountNumber}
              onChange={handleInputChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Bank Name *</label>
            <input
              type="text"
              name="bankName"
              value={formData.bankName}
              onChange={handleInputChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Location *</label>
            <input
              type="text"
              name="bankLocation"
              value={formData.bankLocation}
              onChange={handleInputChange}
              placeholder="Branch location"
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              required
            />
          </div>
        </div>
      )}

      {/* Step 5: Declaration */}
      {currentStep === 5 && (
        <div className="space-y-4">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">5. Declaration</h3>
          
          <div className="p-3 bg-gray-50 rounded-lg space-y-2">
            <p className="text-sm text-gray-700">
              Member <strong>{formData.fullName || '_______________'}</strong> confirms that the information provided is true, accurate and complete.
            </p>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Entrance Fee (UGX)</label>
              <input
                type="number"
                name="entranceFee"
                value={formData.entranceFee}
                onChange={handleInputChange}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-gray-50"
                readOnly
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Passbook Fee (UGX)</label>
              <input
                type="number"
                name="passbookFee"
                value={formData.passbookFee}
                onChange={handleInputChange}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-gray-50"
                readOnly
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Monthly Savings Amount (UGX) *</label>
            <input
              type="number"
              name="monthlySavings"
              value={formData.monthlySavings}
              onChange={handleInputChange}
              placeholder="50000"
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              required
            />
            <p className="text-xs text-gray-500 mt-1">This amount will be deducted from salary monthly</p>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Monthly Savings in Words *</label>
            <input
              type="text"
              name="monthlySavingsWords"
              value={formData.monthlySavingsWords}
              onChange={handleInputChange}
              placeholder="e.g., Fifty Thousand Shillings Only"
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              required
            />
          </div>
        </div>
      )}

      {/* Step 6: Referee/Nominee & Account Setup */}
      {currentStep === 6 && (
        <div className="space-y-4">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">6. Referee/Nominee & Account Setup</h3>
          
          <div className="p-3 bg-blue-50 border border-blue-200 rounded-lg mb-4">
            <p className="text-sm text-blue-800">
              <strong>Note:</strong> Referee/nominee must be an existing active member of the scheme.
            </p>
          </div>

          <div className="relative">
            <label className="block text-sm font-medium text-gray-700 mb-1">Search for Referee/Nominee *</label>
            <input
              type="text"
              value={refereeSearch}
              onChange={handleRefereeSearch}
              placeholder="Search by name, email, or member number..."
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              disabled={!!selectedReferee}
            />
            
            {searchingReferee && (
              <div className="absolute right-3 top-9">
                <div className="animate-spin h-5 w-5 border-2 border-indigo-600 border-t-transparent rounded-full"></div>
              </div>
            )}

            {showRefereeDropdown && refereeResults.length > 0 && (
              <div className="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                {refereeResults.map((member) => (
                  <button
                    key={member.id}
                    type="button"
                    onClick={() => selectReferee(member)}
                    className="w-full px-4 py-3 text-left hover:bg-gray-50 border-b border-gray-100 last:border-b-0"
                  >
                    <div className="font-medium text-gray-900">
                      {member.first_name} {member.last_name}
                    </div>
                    <div className="text-sm text-gray-600">
                      {member.member_number} • {member.email}
                    </div>
                  </button>
                ))}
              </div>
            )}

            {showRefereeDropdown && refereeResults.length === 0 && refereeSearch.length >= 2 && !searchingReferee && (
              <div className="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-4 text-center text-gray-500">
                No members found. Try a different search term.
              </div>
            )}
          </div>

          {selectedReferee && (
            <div className="p-4 bg-green-50 border border-green-200 rounded-lg">
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-900">Selected Referee:</p>
                  <p className="text-sm text-gray-700 mt-1">
                    <strong>{selectedReferee.first_name} {selectedReferee.last_name}</strong>
                  </p>
                  <p className="text-sm text-gray-600">
                    Member No: {selectedReferee.member_number}
                  </p>
                  <p className="text-sm text-gray-600">
                    Contact: {selectedReferee.phone}
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => {
                    setSelectedReferee(null);
                    setRefereeSearch('');
                    setFormData(prev => ({
                      ...prev,
                      refereeName: '',
                      refereeContact: '',
                      refereeMemberNumber: '',
                    }));
                  }}
                  className="text-sm text-red-600 hover:text-red-700"
                >
                  Change
                </button>
              </div>
            </div>
          )}

          <div className="border-t border-gray-200 pt-4 mt-6">
            <h4 className="text-md font-semibold text-gray-900 mb-3">Create Account</h4>
            
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Password *</label>
              <input
                type="password"
                name="password"
                value={formData.password}
                onChange={handleInputChange}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                required
                minLength={8}
              />
              <p className="text-xs text-gray-500 mt-1">Must be at least 8 characters long</p>
            </div>

            <div className="mt-3">
              <label className="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
              <input
                type="password"
                name="confirmPassword"
                value={formData.confirmPassword}
                onChange={handleInputChange}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                required
              />
            </div>
          </div>

          <div className="flex items-start gap-3 p-3 bg-indigo-50 rounded-lg border border-indigo-200">
            <input
              type="checkbox"
              name="activateImmediately"
              checked={formData.activateImmediately}
              onChange={handleInputChange}
              className="mt-1"
            />
            <label className="text-sm text-gray-700">
              <strong>Activate member immediately</strong> (Admin privilege: Skip approval process and activate member account right away)
            </label>
          </div>
        </div>
      )}

      {/* Navigation Buttons */}
      <div className="flex justify-between mt-6 pt-4 border-t">
        {currentStep > 1 && (
          <button
            type="button"
            onClick={handlePrevious}
            className="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors"
          >
            Previous
          </button>
        )}
        
        {currentStep < 6 ? (
          <button
            type="button"
            onClick={handleNext}
            className="ml-auto px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors"
          >
            Next
          </button>
        ) : (
          <button
            type="submit"
            disabled={isSubmitting}
            className="ml-auto px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isSubmitting ? 'Registering...' : 'Register Member'}
          </button>
        )}
      </div>
    </form>
  );
};

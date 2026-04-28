import { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { Logo } from '../components/Logo';

interface Member {
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
  
  // Agreement
  termsAccepted: boolean;
  declarationAccepted: boolean;
}

export const Register: React.FC = () => {
  const navigate = useNavigate();
  const [currentStep, setCurrentStep] = useState(1);
  
  // Prevent default drag and drop behavior globally
  useEffect(() => {
    const preventDefaults = (e: DragEvent) => {
      e.preventDefault();
      e.stopPropagation();
    };

    const handleDrop = (e: DragEvent) => {
      e.preventDefault();
      e.stopPropagation();
    };

    // Add event listeners to prevent default drag and drop
    document.addEventListener('dragenter', preventDefaults, false);
    document.addEventListener('dragover', preventDefaults, false);
    document.addEventListener('dragleave', preventDefaults, false);
    document.addEventListener('drop', handleDrop, false);

    return () => {
      // Cleanup event listeners
      document.removeEventListener('dragenter', preventDefaults, false);
      document.removeEventListener('dragover', preventDefaults, false);
      document.removeEventListener('dragleave', preventDefaults, false);
      document.removeEventListener('drop', handleDrop, false);
    };
  }, []);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);
  
  // Referee search state
  const [refereeSearch, setRefereeSearch] = useState('');
  const [searchingReferee, setSearchingReferee] = useState(false);
  const [refereeResults, setRefereeResults] = useState<Member[]>([]);
  const [selectedReferee, setSelectedReferee] = useState<Member | null>(null);
  const [showRefereeDropdown, setShowRefereeDropdown] = useState(false);
  
  // Passport photo state
  const [passportPhoto, setPassportPhoto] = useState<File | null>(null);
  const [photoPreview, setPhotoPreview] = useState<string | null>(null);
  
  const [formData, setFormData] = useState<RegistrationForm>({
    fullName: '',
    gender: '',
    maritalStatus: '',
    nationality: 'Ugandan',
    village: '',
    district: '',
    mobileContact: '',
    email: '',
    nationalId: '',
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
    termsAccepted: false,
    declarationAccepted: false,
    category: '',
  });

  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [passwordStrength, setPasswordStrength] = useState<'weak' | 'medium' | 'strong' | null>(null);

  const calculatePasswordStrength = (password: string): 'weak' | 'medium' | 'strong' => {
    let strength = 0;
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;

    if (strength <= 2) return 'weak';
    if (strength <= 4) return 'medium';
    return 'strong';
  };

  const validateField = (name: string, value: string): string => {
    switch (name) {
      case 'email':
        if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
          return 'Invalid email format';
        }
        break;
      case 'mobileContact':
      case 'nextOfKinContact':
        if (value && value.replace(/\D/g, '').length < 10) {
          return 'Phone must be at least 10 digits';
        }
        break;
      case 'nationalId':
        if (value && value.length < 8) {
          return 'National ID must be at least 8 characters';
        }
        break;
      case 'password':
        if (value && value.length < 8) {
          return 'Password must be at least 8 characters';
        }
        if (value && !/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(value)) {
          return 'Password must contain uppercase, lowercase, and number';
        }
        break;
      case 'confirmPassword':
        if (value && value !== formData.password) {
          return 'Passwords do not match';
        }
        break;
      case 'monthlySavings':
        const amount = parseFloat(value);
        if (value && (isNaN(amount) || amount < 10000)) {
          return 'Minimum monthly savings is UGX 10,000';
        }
        break;
    }
    return '';
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value, type } = e.target;
    const newValue = type === 'checkbox' ? (e.target as HTMLInputElement).checked : value;
    
    setFormData(prev => ({
      ...prev,
      [name]: newValue
    }));

    // Real-time validation for specific fields
    if (type !== 'checkbox' && typeof newValue === 'string') {
      const error = validateField(name, newValue);
      setFieldErrors(prev => ({
        ...prev,
        [name]: error
      }));

      // Update password strength
      if (name === 'password' && newValue) {
        setPasswordStrength(calculatePasswordStrength(newValue));
      }
    }
  };

  // Search for referee members
  const searchReferee = async (searchTerm: string) => {
    if (searchTerm.length < 2) {
      setRefereeResults([]);
      return;
    }

    setSearchingReferee(true);
    try {
      const response = await fetch(`http://localhost:8000/api/members/search?q=${encodeURIComponent(searchTerm)}`);
      const data = await response.json();
      
      if (response.ok) {
        setRefereeResults(data.data || []);
        setShowRefereeDropdown(true);
      }
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

  const selectReferee = (member: Member) => {
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
            !formData.village || !formData.district || !formData.mobileContact || !formData.email || !formData.nationalId) {
          setError('Please fill in all personal details fields');
          return false;
        }
        if (formData.fullName.trim().split(' ').length < 2) {
          setError('Please enter your full name (first and last name)');
          return false;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
          setError('Please enter a valid email address');
          return false;
        }
        if (!/^[+]?[\d\s()-]+$/.test(formData.mobileContact)) {
          setError('Please enter a valid phone number');
          return false;
        }
        if (formData.mobileContact.replace(/\D/g, '').length < 10) {
          setError('Phone number must be at least 10 digits');
          return false;
        }
        if (formData.nationalId.length < 8) {
          setError('National ID must be at least 8 characters');
          return false;
        }
        break;
      case 2:
        if (!formData.nextOfKinName || !formData.nextOfKinResidence || !formData.nextOfKinContact || !formData.nextOfKinRelationship) {
          setError('Please fill in all next of kin details');
          return false;
        }
        if (formData.nextOfKinName.trim().split(' ').length < 2) {
          setError('Please enter next of kin full name (first and last name)');
          return false;
        }
        if (!/^[+]?[\d\s()-]+$/.test(formData.nextOfKinContact)) {
          setError('Please enter a valid next of kin phone number');
          return false;
        }
        if (formData.nextOfKinContact.replace(/\D/g, '').length < 10) {
          setError('Next of kin phone number must be at least 10 digits');
          return false;
        }
        break;
      case 3:
        if (!formData.occupation || !formData.sourceOfIncome) {
          setError('Please fill in employment details');
          return false;
        }
        if (formData.occupation.trim().length < 3) {
          setError('Please enter a valid occupation');
          return false;
        }
        if (formData.sourceOfIncome === 'other' && !formData.otherSourceOfIncome) {
          setError('Please specify other source of income');
          return false;
        }
        if (formData.sourceOfIncome === 'salaried' && !formData.organization) {
          setError('Please enter your organization/employer name');
          return false;
        }
        break;
      case 4:
        if (!formData.bankAccountName || !formData.bankAccountNumber || !formData.bankName || !formData.bankLocation) {
          setError('Please fill in all bank details');
          return false;
        }
        if (formData.bankAccountName.trim().length < 3) {
          setError('Please enter a valid bank account name');
          return false;
        }
        if (formData.bankAccountNumber.replace(/\D/g, '').length < 8) {
          setError('Bank account number must be at least 8 digits');
          return false;
        }
        if (formData.bankName.trim().length < 3) {
          setError('Please enter a valid bank name');
          return false;
        }
        break;
      case 5:
        if (!formData.monthlySavings) {
          setError('Please enter monthly savings amount');
          return false;
        }
        const savingsAmount = parseFloat(formData.monthlySavings);
        if (isNaN(savingsAmount) || savingsAmount <= 0) {
          setError('Monthly savings must be a positive number');
          return false;
        }
        if (savingsAmount < 10000) {
          setError('Monthly savings must be at least UGX 10,000');
          return false;
        }
        if (!formData.monthlySavingsWords || formData.monthlySavingsWords.trim().length < 5) {
          setError('Please enter monthly savings amount in words');
          return false;
        }
        if (!formData.declarationAccepted) {
          setError('You must accept the declaration to proceed');
          return false;
        }
        break;
      case 6:
        if (!selectedReferee || !formData.refereeMemberNumber) {
          setError('Please select a referee/nominee from existing members');
          return false;
        }
        if (!formData.password || !formData.confirmPassword) {
          setError('Please enter and confirm your password');
          return false;
        }
        if (formData.password.length < 8) {
          setError('Password must be at least 8 characters long');
          return false;
        }
        if (!/(?=.*[a-z])/.test(formData.password)) {
          setError('Password must contain at least one lowercase letter');
          return false;
        }
        if (!/(?=.*[A-Z])/.test(formData.password)) {
          setError('Password must contain at least one uppercase letter');
          return false;
        }
        if (!/(?=.*\d)/.test(formData.password)) {
          setError('Password must contain at least one number');
          return false;
        }
        if (formData.password !== formData.confirmPassword) {
          setError('Passwords do not match');
          return false;
        }
        if (!formData.termsAccepted) {
          setError('You must accept the terms and conditions');
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

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateStep(6)) return;
    
    setIsLoading(true);
    setError(null);

    try {
      // Create FormData for file upload
      const formDataToSend = new FormData();
      
      // 1. Personal Details
      formDataToSend.append('full_name', formData.fullName);
      formDataToSend.append('gender', formData.gender);
      formDataToSend.append('marital_status', formData.maritalStatus);
      formDataToSend.append('nationality', formData.nationality);
      formDataToSend.append('village', formData.village);
      formDataToSend.append('district', formData.district);
      formDataToSend.append('mobile_contact', formData.mobileContact);
      formDataToSend.append('email', formData.email);
      formDataToSend.append('national_id', formData.nationalId);
      formDataToSend.append('category', formData.category);
      
      // Passport photo
      if (passportPhoto) {
        formDataToSend.append('passport_photo', passportPhoto);
      }
      
      // 2. Next of Kin Details
      formDataToSend.append('next_of_kin_name', formData.nextOfKinName);
      formDataToSend.append('next_of_kin_residence', formData.nextOfKinResidence);
      formDataToSend.append('next_of_kin_contact', formData.nextOfKinContact);
      formDataToSend.append('next_of_kin_relationship', formData.nextOfKinRelationship);
      
      // 3. Employment Details
      formDataToSend.append('occupation', formData.occupation);
      formDataToSend.append('source_of_income', formData.sourceOfIncome);
      formDataToSend.append('other_source_of_income', formData.otherSourceOfIncome);
      formDataToSend.append('organization', formData.organization);
      formDataToSend.append('job_title', formData.jobTitle);
      
      // 4. Bank Details
      formDataToSend.append('bank_account_name', formData.bankAccountName);
      formDataToSend.append('bank_account_number', formData.bankAccountNumber);
      formDataToSend.append('bank_name', formData.bankName);
      formDataToSend.append('bank_location', formData.bankLocation);
      
      // 5. Declaration
      formDataToSend.append('entrance_fee', formData.entranceFee);
      formDataToSend.append('passbook_fee', formData.passbookFee);
      formDataToSend.append('monthly_savings', formData.monthlySavings);
      formDataToSend.append('monthly_savings_words', formData.monthlySavingsWords);
      
      // 6. Referee/Nominee
      formDataToSend.append('referee_name', formData.refereeName);
      formDataToSend.append('referee_contact', formData.refereeContact);
      formDataToSend.append('referee_member_number', formData.refereeMemberNumber);
      
      // Account
      formDataToSend.append('password', formData.password);

      const response = await fetch('http://localhost:8000/api/members/register', {
        method: 'POST',
        body: formDataToSend, // Send FormData instead of JSON
      });

      const data = await response.json();

      if (!response.ok) {
        // Handle specific validation errors
        if (response.status === 422 && data.errors) {
          const errorMessages = Object.values(data.errors).flat();
          throw new Error(errorMessages.join(', '));
        }
        throw new Error(data.message || 'Registration failed. Please try again.');
      }

      setSuccess(true);
      setTimeout(() => {
        navigate('/login', { 
          state: { message: 'Registration successful! Please wait for admin approval before logging in.' }
        });
      }, 3000);
    } catch (err: any) {
      console.error('Registration error:', err);
      
      // Handle network errors
      if (err.message === 'Failed to fetch' || err.name === 'TypeError') {
        setError('Network error. Please check your internet connection and try again.');
      } else if (err.message.includes('email')) {
        setError('This email is already registered. Please use a different email or try logging in.');
        setCurrentStep(1); // Go back to step 1 where email is
      } else if (err.message.includes('national_id')) {
        setError('This National ID is already registered. Please check your details or contact support.');
        setCurrentStep(1); // Go back to step 1 where national ID is
      } else {
        setError(err.message || 'Registration failed. Please try again.');
      }
    } finally {
      setIsLoading(false);
    }
  };

  const renderStepIndicator = () => (
    <div className="flex items-center justify-center mb-6 sm:mb-8 overflow-x-auto pb-2">
      {[1, 2, 3, 4, 5, 6].map((step) => (
        <div key={step} className="flex items-center flex-shrink-0">
          <div className={`w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-semibold text-xs sm:text-sm ${
            step === currentStep 
              ? 'bg-indigo-600 text-white' 
              : step < currentStep 
              ? 'bg-green-500 text-white' 
              : 'bg-gray-200 text-gray-600'
          }`}>
            {step < currentStep ? '✓' : step}
          </div>
          {step < 6 && (
            <div className={`w-8 sm:w-12 h-1 ${step < currentStep ? 'bg-green-500' : 'bg-gray-200'}`} />
          )}
        </div>
      ))}
    </div>
  );

  if (success) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-indigo-50 flex items-center justify-center p-4">
        <div className="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center">
          <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg className="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h2 className="text-2xl font-bold text-gray-900 mb-2">Registration Successful!</h2>
          <p className="text-gray-600 mb-4">
            Your application has been submitted successfully. Please wait for admin approval before you can log in.
          </p>
          <p className="text-sm text-gray-500">Redirecting to login page...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-indigo-50 flex items-center justify-center p-4">
      <div className="max-w-2xl w-full">
        {/* Header */}
        <div className="text-center mb-6 sm:mb-8">
          <div className="flex justify-center mb-4">
            <Logo size="lg" />
          </div>
          <h1 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">Join Kitovu Hospital Staff Saving Scheme</h1>
          <p className="text-sm sm:text-base text-gray-600">Complete the registration form to become a member</p>
        </div>

        {/* Registration Form */}
        <div className="bg-white rounded-2xl shadow-xl p-4 sm:p-6 lg:p-8">
          {!success && renderStepIndicator()}

          {error && (
            <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
              <svg className="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <p className="text-sm text-red-800">{error}</p>
            </div>
          )}

          {success && (
            <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start gap-3">
              <svg className="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div>
                <p className="text-sm font-medium text-green-800">Application Successfully Submitted!</p>
                <p className="text-sm text-green-700 mt-1">
                  Your membership application has been received. You will be redirected to the login page shortly. 
                  Please wait for admin approval before attempting to log in.
                </p>
              </div>
            </div>
          )}

          {!success && (
            <form onSubmit={handleSubmit}>
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
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
                    required
                  />
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                    <select
                      name="gender"
                      value={formData.gender}
                      onChange={handleInputChange}
                      className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
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
                      className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
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
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
                    required
                  />
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Village *</label>
                    <input
                      type="text"
                      name="village"
                      value={formData.village}
                      onChange={handleInputChange}
                      className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
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
                      className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
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
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
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
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
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
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Member Category *</label>
                  <select
                    name="category"
                    value={formData.category}
                    onChange={handleInputChange}
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
                    required
                  >
                    <option value="">Select Category</option>
                    <option value="staff">1. Staff</option>
                    <option value="act_program">2. ACT Program</option>
                    <option value="nursing_school">3. Nursing School</option>
                    <option value="hc_staff">4. HC Staff</option>
                    <option value="non_hospital_staff">5. Non Hospital Staff</option>
                  </select>
                  <p className="text-xs text-gray-500 mt-1">Select the category you belong to at Kitovu Hospital</p>
                </div>

                {/* Passport Photo Upload */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Passport Photo (Optional)</label>
                  <div className="flex items-start gap-4">
                    {/* Photo Preview */}
                    {photoPreview ? (
                      <div className="flex-shrink-0">
                        <div className="relative">
                          <img
                            src={photoPreview}
                            alt="Passport preview"
                            className="w-24 h-24 sm:w-32 sm:h-32 object-cover rounded-lg border-2 border-indigo-200"
                          />
                          <div className="absolute -top-2 -right-2">
                            <button
                              type="button"
                              onClick={() => {
                                setPassportPhoto(null);
                                setPhotoPreview(null);
                              }}
                              className="bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 transition-colors"
                              title="Remove photo"
                            >
                              ×
                            </button>
                          </div>
                        </div>
                        <p className="text-xs text-center text-gray-600 mt-2">Photo uploaded</p>
                      </div>
                    ) : null}
                    
                    {/* Upload Button */}
                    <div className="flex-1">
                      <label 
                        className="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-indigo-500 hover:bg-indigo-50 transition-colors"
                        onDragOver={(e) => {
                          e.preventDefault();
                          e.stopPropagation();
                          e.currentTarget.classList.add('border-indigo-500', 'bg-indigo-50');
                        }}
                        onDragLeave={(e) => {
                          e.preventDefault();
                          e.stopPropagation();
                          e.currentTarget.classList.remove('border-indigo-500', 'bg-indigo-50');
                        }}
                        onDrop={(e) => {
                          e.preventDefault();
                          e.stopPropagation();
                          e.currentTarget.classList.remove('border-indigo-500', 'bg-indigo-50');
                          
                          const files = e.dataTransfer.files;
                          if (files && files[0]) {
                            const file = files[0];
                            
                            // Validate file size (5MB)
                            if (file.size > 5 * 1024 * 1024) {
                              setError('Photo size must be less than 5MB');
                              return;
                            }
                            
                            // Validate file type
                            if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
                              setError('Only JPG and PNG images are allowed');
                              return;
                            }
                            
                            setPassportPhoto(file);
                            
                            // Create preview
                            const reader = new FileReader();
                            reader.onloadend = () => {
                              setPhotoPreview(reader.result as string);
                            };
                            reader.readAsDataURL(file);
                            setError(null);
                          }
                        }}
                      >
                        <div className="flex flex-col items-center justify-center pt-5 pb-6">
                          <svg className="w-8 h-8 mb-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                          </svg>
                          <p className="text-xs sm:text-sm text-gray-500">
                            <span className="font-semibold">Click to upload</span> or drag and drop
                          </p>
                          <p className="text-xs text-gray-400 mt-1">PNG, JPG up to 5MB</p>
                        </div>
                        <input
                          type="file"
                          className="hidden"
                          accept="image/jpeg,image/png,image/jpg"
                          onChange={(e) => {
                            const file = e.target.files?.[0];
                            if (file) {
                              // Validate file size (5MB)
                              if (file.size > 5 * 1024 * 1024) {
                                setError('Photo size must be less than 5MB');
                                return;
                              }
                              
                              // Validate file type
                              if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
                                setError('Only JPG and PNG images are allowed');
                                return;
                              }
                              
                              setPassportPhoto(file);
                              
                              // Create preview
                              const reader = new FileReader();
                              reader.onloadend = () => {
                                setPhotoPreview(reader.result as string);
                              };
                              reader.readAsDataURL(file);
                              setError(null);
                            }
                          }}
                        />
                      </label>
                    </div>
                  </div>
                  <p className="text-xs text-gray-500 mt-2">
                    Upload a clear passport-size photo. This helps us verify your identity.
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
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
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
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
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
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Relationship to the Next of Kin *</label>
                  <select
                    name="nextOfKinRelationship"
                    value={formData.nextOfKinRelationship}
                    onChange={handleInputChange}
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
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
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Source of Income *</label>
                  <select
                    name="sourceOfIncome"
                    value={formData.sourceOfIncome}
                    onChange={handleInputChange}
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
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
                      className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
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
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Job Title/Designation</label>
                  <input
                    type="text"
                    name="jobTitle"
                    value={formData.jobTitle}
                    onChange={handleInputChange}
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
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
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
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
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
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
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
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
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
                    required
                  />
                </div>
              </div>
            )}

            {/* Step 5: Declaration */}
            {currentStep === 5 && (
              <div className="space-y-4">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">5. Declaration</h3>
                
                <div className="p-4 bg-gray-50 rounded-lg space-y-3">
                  <p className="text-sm text-gray-700">
                    I <strong>{formData.fullName || '_______________'}</strong> confirm that the information provided in this form and other documents provided to the scheme is true, accurate and complete.
                  </p>
                  <p className="text-sm text-gray-700">
                    I further agree to abide by the bye laws and additional terms governing the scheme.
                  </p>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Entrance Fee (UGX)</label>
                    <input
                      type="number"
                      name="entranceFee"
                      value={formData.entranceFee}
                      onChange={handleInputChange}
                      className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-gray-50 text-sm sm:text-base"
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
                      className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-gray-50 text-sm sm:text-base"
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
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
                    required
                  />
                  <p className="text-xs text-gray-500 mt-1">This amount will be deducted from your salary monthly</p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Monthly Savings in Words *</label>
                  <input
                    type="text"
                    name="monthlySavingsWords"
                    value={formData.monthlySavingsWords}
                    onChange={handleInputChange}
                    placeholder="e.g., Fifty Thousand Shillings Only"
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
                    required
                  />
                </div>

                <div className="flex items-start gap-3 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                  <input
                    type="checkbox"
                    name="declarationAccepted"
                    checked={formData.declarationAccepted}
                    onChange={handleInputChange}
                    className="mt-1"
                    required
                  />
                  <label className="text-sm text-gray-700">
                    I authorize the scheme to deduct the entrance fee, passbook fee, and monthly savings from my salary as indicated above. *
                  </label>
                </div>
              </div>
            )}

            {/* Step 6: Referee/Nominee & Account Setup */}
            {currentStep === 6 && (
              <div className="space-y-4">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">6. Referee/Nominee & Account Setup</h3>
                
                <div className="p-3 bg-blue-50 border border-blue-200 rounded-lg mb-4">
                  <p className="text-sm text-blue-800">
                    <strong>Note:</strong> Your referee/nominee must be an existing active member of the scheme.
                  </p>
                </div>

                <div className="relative">
                  <label className="block text-sm font-medium text-gray-700 mb-1">Search for Referee/Nominee *</label>
                  <input
                    type="text"
                    value={refereeSearch}
                    onChange={handleRefereeSearch}
                    placeholder="Search by name, email, or member number..."
                    className="w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm sm:text-base"
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
                  <h4 className="text-md font-semibold text-gray-900 mb-3">Create Your Account</h4>
                  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                    <input
                      type="password"
                      name="password"
                      value={formData.password}
                      onChange={handleInputChange}
                      className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent ${
                        fieldErrors.password ? 'border-red-500' : 'border-gray-300'
                      }`}
                      required
                      minLength={8}
                    />
                    {fieldErrors.password && (
                      <p className="text-xs text-red-600 mt-1">{fieldErrors.password}</p>
                    )}
                    {passwordStrength && formData.password && !fieldErrors.password && (
                      <div className="mt-2">
                        <div className="flex gap-1">
                          <div className={`h-1 flex-1 rounded ${passwordStrength === 'weak' ? 'bg-red-500' : passwordStrength === 'medium' ? 'bg-yellow-500' : 'bg-green-500'}`}></div>
                          <div className={`h-1 flex-1 rounded ${passwordStrength === 'medium' || passwordStrength === 'strong' ? passwordStrength === 'medium' ? 'bg-yellow-500' : 'bg-green-500' : 'bg-gray-200'}`}></div>
                          <div className={`h-1 flex-1 rounded ${passwordStrength === 'strong' ? 'bg-green-500' : 'bg-gray-200'}`}></div>
                        </div>
                        <p className={`text-xs mt-1 ${passwordStrength === 'weak' ? 'text-red-600' : passwordStrength === 'medium' ? 'text-yellow-600' : 'text-green-600'}`}>
                          Password strength: {passwordStrength}
                        </p>
                      </div>
                    )}
                    <p className="text-xs text-gray-500 mt-1">Must contain uppercase, lowercase, and number</p>
                  </div>

                  <div className="mt-3">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                    <input
                      type="password"
                      name="confirmPassword"
                      value={formData.confirmPassword}
                      onChange={handleInputChange}
                      className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent ${
                        fieldErrors.confirmPassword ? 'border-red-500' : 'border-gray-300'
                      }`}
                      required
                    />
                    {fieldErrors.confirmPassword && (
                      <p className="text-xs text-red-600 mt-1">{fieldErrors.confirmPassword}</p>
                    )}
                    {!fieldErrors.confirmPassword && formData.confirmPassword && formData.password === formData.confirmPassword && (
                      <p className="text-xs text-green-600 mt-1 flex items-center gap-1">
                        <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                        </svg>
                        Passwords match
                      </p>
                    )}
                  </div>
                </div>

                <div className="flex items-start gap-3 p-4 bg-gray-50 rounded-lg">
                  <input
                    type="checkbox"
                    name="termsAccepted"
                    checked={formData.termsAccepted}
                    onChange={handleInputChange}
                    className="mt-1"
                    required
                  />
                  <label className="text-sm text-gray-700">
                    I agree to the <a href="#" className="text-indigo-600 hover:underline">Terms and Conditions</a> and <a href="#" className="text-indigo-600 hover:underline">Privacy Policy</a> of Kitovu Hospital Staff Saving Scheme *
                  </label>
                </div>
              </div>
            )}

            {/* Navigation Buttons */}
            <div className="flex flex-col sm:flex-row justify-between gap-3 mt-6 sm:mt-8">
              {currentStep > 1 && (
                <button
                  type="button"
                  onClick={handlePrevious}
                  className="px-4 sm:px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm sm:text-base order-2 sm:order-1"
                >
                  Previous
                </button>
              )}
              
              {currentStep < 6 ? (
                <button
                  type="button"
                  onClick={handleNext}
                  className="sm:ml-auto px-4 sm:px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm sm:text-base order-1 sm:order-2"
                >
                  Next
                </button>
              ) : (
                <button
                  type="submit"
                  disabled={isLoading}
                  className="sm:ml-auto px-4 sm:px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-sm sm:text-base order-1 sm:order-2"
                >
                  {isLoading ? 'Submitting...' : 'Submit Registration'}
                </button>
              )}
            </div>
          </form>
          )}

          <div className="mt-4 sm:mt-6 text-center">
            <p className="text-xs sm:text-sm text-gray-600">
              Already have an account?{' '}
              <Link to="/login" className="text-indigo-600 hover:underline font-medium">
                Login here
              </Link>
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

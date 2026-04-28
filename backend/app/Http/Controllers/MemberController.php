<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\User;
use App\Models\SavingsAccount;
use App\Models\LoanGuarantor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    /**
     * Member self-registration (public endpoint)
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            // 1. Personal Details
            'full_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'marital_status' => 'required|string|max:50',
            'nationality' => 'required|string|max:100',
            'village' => 'required|string|max:255',
            'district' => 'required|string|max:100',
            'mobile_contact' => 'required|string|max:20',
            'email' => 'required|email|unique:members,email|unique:users,email',
            'national_id' => 'required|string|unique:members,national_id',
            'category' => 'required|in:staff,act_program,nursing_school,hc_staff,non_hospital_staff',
            
            // 2. Next of Kin Details
            'next_of_kin_name' => 'required|string|max:255',
            'next_of_kin_residence' => 'required|string|max:255',
            'next_of_kin_contact' => 'required|string|max:20',
            'next_of_kin_relationship' => 'required|string|max:100',
            
            // 3. Employment Details
            'occupation' => 'required|string|max:255',
            'source_of_income' => 'required|in:salaried,self-employed,other',
            'other_source_of_income' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            
            // 4. Bank Details
            'bank_account_name' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:50',
            'bank_name' => 'required|string|max:255',
            'bank_location' => 'required|string|max:255',
            
            // 5. Declaration
            'entrance_fee' => 'required|numeric|min:0',
            'passbook_fee' => 'required|numeric|min:0',
            'monthly_savings' => 'required|numeric|min:0',
            'monthly_savings_words' => 'nullable|string|max:255',
            
            // 6. Referee/Nominee
            'referee_name' => 'required|string|max:255',
            'referee_contact' => 'required|string|max:20',
            'referee_member_number' => 'required|string|exists:members,member_number',
            
            // Account
            'password' => 'required|string|min:8',
        ]);

        DB::beginTransaction();
        try {
            // Generate member number
            $lastMember = Member::orderBy('id', 'desc')->first();
            $nextNumber = $lastMember ? intval(substr($lastMember->member_number, 3)) + 1 : 1;
            $memberNumber = 'MEM' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            // Split full name into first and last name
            $nameParts = explode(' ', $validated['full_name'], 2);
            $firstName = $nameParts[0];
            $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

            // Create user account (inactive until approved)
            $user = User::create([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'member',
                'status' => 'inactive', // Requires admin approval
            ]);

            // Prepare employment info JSON
            $employmentInfo = [
                'occupation' => $validated['occupation'],
                'source_of_income' => $validated['source_of_income'],
                'other_source_of_income' => $validated['other_source_of_income'] ?? null,
                'organization' => $validated['organization'] ?? null,
                'job_title' => $validated['job_title'] ?? null,
                'marital_status' => $validated['marital_status'],
                'nationality' => $validated['nationality'],
                'village' => $validated['village'],
                'district' => $validated['district'],
                'next_of_kin' => [
                    'name' => $validated['next_of_kin_name'],
                    'residence' => $validated['next_of_kin_residence'],
                    'contact' => $validated['next_of_kin_contact'],
                    'relationship' => $validated['next_of_kin_relationship'],
                ],
                'bank_details' => [
                    'account_name' => $validated['bank_account_name'],
                    'account_number' => $validated['bank_account_number'],
                    'bank_name' => $validated['bank_name'],
                    'bank_location' => $validated['bank_location'],
                ],
                'fees' => [
                    'entrance_fee' => $validated['entrance_fee'],
                    'passbook_fee' => $validated['passbook_fee'],
                    'monthly_savings' => $validated['monthly_savings'],
                    'monthly_savings_words' => $validated['monthly_savings_words'] ?? null,
                ],
                'referee' => [
                    'name' => $validated['referee_name'],
                    'contact' => $validated['referee_contact'],
                    'member_number' => $validated['referee_member_number'],
                ],
            ];

            // Handle passport photo upload
            $passportPhotoPath = null;
            if ($request->hasFile('passport_photo')) {
                $file = $request->file('passport_photo');
                
                // Validate file
                $request->validate([
                    'passport_photo' => 'image|mimes:jpeg,png,jpg|max:5120', // 5MB max
                ]);
                
                // Store file
                $passportPhotoPath = $file->store('passport_photos', 'public');
            }

            // Create member record
            $member = Member::create([
                'user_id' => $user->id,
                'member_number' => $memberNumber,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'national_id' => $validated['national_id'],
                'passport_photo' => $passportPhotoPath,
                'email' => $validated['email'],
                'phone' => $validated['mobile_contact'],
                'address' => $validated['village'] . ', ' . $validated['district'],
                'employment_info' => $employmentInfo,
                'status' => 'inactive', // Inactive until approved by admin
                'category' => $validated['category'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registration successful! Your application is pending approval.',
                'data' => [
                    'member_number' => $memberNumber,
                    'email' => $validated['email'],
                    'full_name' => $validated['full_name'],
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search members for referee selection (public endpoint)
     * Only returns active members
     */
    public function searchForReferee(Request $request)
    {
        $search = $request->get('q', '');
        
        if (strlen($search) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $members = Member::where('status', 'active')
            ->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('member_number', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
            })
            ->select('id', 'member_number', 'first_name', 'last_name', 'phone', 'email')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $members,
        ]);
    }

    /**
     * Display a listing of members.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Member::class);

        $query = Member::with(['user', 'savingsAccount']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by name, member number, national ID, or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('member_number', 'like', "%{$search}%")
                  ->orWhere('national_id', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $members = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Add has_savings_account flag to each member
        $members->getCollection()->transform(function ($member) {
            $member->has_savings_account = $member->savingsAccount !== null;
            return $member;
        });

        return response()->json($members);
    }

    /**
     * Store a newly created member.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Member::class);

        $validated = $request->validate([
            // 1. Personal Details
            'full_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'marital_status' => 'required|string|max:50',
            'nationality' => 'required|string|max:100',
            'village' => 'required|string|max:255',
            'district' => 'required|string|max:100',
            'mobile_contact' => 'required|string|max:20',
            'email' => 'required|email|unique:members,email|unique:users,email',
            'national_id' => 'required|string|unique:members,national_id',
            
            // 2. Next of Kin Details
            'next_of_kin_name' => 'required|string|max:255',
            'next_of_kin_residence' => 'required|string|max:255',
            'next_of_kin_contact' => 'required|string|max:20',
            'next_of_kin_relationship' => 'required|string|max:100',
            
            // 3. Employment Details
            'occupation' => 'required|string|max:255',
            'source_of_income' => 'required|in:salaried,self-employed,other',
            'other_source_of_income' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            
            // 4. Bank Details
            'bank_account_name' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:50',
            'bank_name' => 'required|string|max:255',
            'bank_location' => 'required|string|max:255',
            
            // 5. Declaration
            'entrance_fee' => 'required|numeric|min:0',
            'passbook_fee' => 'required|numeric|min:0',
            'monthly_savings' => 'required|numeric|min:0',
            'monthly_savings_words' => 'nullable|string|max:255',
            
            // 6. Referee/Nominee
            'referee_name' => 'required|string|max:255',
            'referee_contact' => 'required|string|max:20',
            'referee_member_number' => 'required|string|exists:members,member_number',
            
            // Account
            'password' => 'required|string|min:8',
            
            // Admin-specific
            'activate_immediately' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            // Generate member number
            $lastMember = Member::orderBy('id', 'desc')->first();
            $nextNumber = $lastMember ? intval(substr($lastMember->member_number, 3)) + 1 : 1;
            $memberNumber = 'MEM' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            // Split full name into first and last name
            $nameParts = explode(' ', $validated['full_name'], 2);
            $firstName = $nameParts[0];
            $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

            // Determine status based on activate_immediately flag
            $status = isset($validated['activate_immediately']) && $validated['activate_immediately'] 
                ? 'active' 
                : 'inactive';

            // Create user account
            $user = User::create([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'member',
                'status' => $status,
            ]);

            // Prepare employment info JSON
            $employmentInfo = [
                'occupation' => $validated['occupation'],
                'source_of_income' => $validated['source_of_income'],
                'other_source_of_income' => $validated['other_source_of_income'] ?? null,
                'organization' => $validated['organization'] ?? null,
                'job_title' => $validated['job_title'] ?? null,
                'marital_status' => $validated['marital_status'],
                'nationality' => $validated['nationality'],
                'village' => $validated['village'],
                'district' => $validated['district'],
                'next_of_kin' => [
                    'name' => $validated['next_of_kin_name'],
                    'residence' => $validated['next_of_kin_residence'],
                    'contact' => $validated['next_of_kin_contact'],
                    'relationship' => $validated['next_of_kin_relationship'],
                ],
                'bank_details' => [
                    'account_name' => $validated['bank_account_name'],
                    'account_number' => $validated['bank_account_number'],
                    'bank_name' => $validated['bank_name'],
                    'bank_location' => $validated['bank_location'],
                ],
                'fees' => [
                    'entrance_fee' => $validated['entrance_fee'],
                    'passbook_fee' => $validated['passbook_fee'],
                    'monthly_savings' => $validated['monthly_savings'],
                    'monthly_savings_words' => $validated['monthly_savings_words'] ?? null,
                ],
                'referee' => [
                    'name' => $validated['referee_name'],
                    'contact' => $validated['referee_contact'],
                    'member_number' => $validated['referee_member_number'],
                ],
                'registered_by_admin' => auth()->user()->name,
                'registered_at' => now()->toIso8601String(),
            ];

            // Create member record
            $member = Member::create([
                'user_id' => $user->id,
                'member_number' => $memberNumber,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'national_id' => $validated['national_id'],
                'email' => $validated['email'],
                'phone' => $validated['mobile_contact'],
                'address' => $validated['village'] . ', ' . $validated['district'],
                'employment_info' => $employmentInfo,
                'status' => $status,
            ]);

            // Create savings account if activated immediately
            if ($status === 'active') {
                SavingsAccount::create([
                    'member_id' => $member->id,
                    'account_number' => SavingsAccount::generateAccountNumber(),
                    'balance' => 0,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => $status === 'active' 
                    ? 'Member registered and activated successfully' 
                    : 'Member registered successfully. Pending approval.',
                'member' => $member->load('user', 'savingsAccount'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to register member',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified member.
     */
    public function show(string $id)
    {
        $member = Member::with(['user', 'savingsAccount', 'loans'])->findOrFail($id);

        $this->authorize('view', $member);

        return response()->json([
            'member' => $member,
        ]);
    }

    /**
     * Update the specified member.
     */
    public function update(Request $request, string $id)
    {
        $member = Member::findOrFail($id);

        $this->authorize('update', $member);

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'address' => 'sometimes|required|string',
            'employment_info' => 'nullable|array',
            'status' => ['sometimes', 'required', Rule::in([
                Member::STATUS_ACTIVE,
                Member::STATUS_SUSPENDED,
                Member::STATUS_INACTIVE,
            ])],
        ]);

        // Check if trying to change status
        if (isset($validated['status']) && $validated['status'] !== $member->status) {
            $this->authorize('changeStatus', Member::class);
        }

        $member->update($validated);

        // Update user name if first or last name changed
        if (isset($validated['first_name']) || isset($validated['last_name'])) {
            $member->user->update([
                'name' => $member->full_name,
            ]);
        }

        return response()->json([
            'message' => 'Member updated successfully',
            'member' => $member->load('user'),
        ]);
    }

    /**
     * Remove the specified member (soft delete).
     */
    public function destroy(string $id)
    {
        $member = Member::findOrFail($id);

        $this->authorize('delete', $member);

        // Soft delete by setting status to inactive
        $member->update(['status' => Member::STATUS_INACTIVE]);
        $member->user->update(['status' => User::STATUS_INACTIVE]);

        return response()->json([
            'message' => 'Member deleted successfully',
        ]);
    }

    /**
     * Approve a pending member registration
     */
    public function approve(string $id)
    {
        $member = Member::with('user')->findOrFail($id);

        // Only super_admin and loan_officer can approve members
        if (!in_array(auth()->user()->role, ['super_admin', 'loan_officer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to approve members',
            ], 403);
        }

        if ($member->status !== 'inactive') {
            return response()->json([
                'success' => false,
                'message' => 'Only inactive members can be approved',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Activate member
            $member->update(['status' => 'active']);
            
            // Activate user account
            $member->user->update(['status' => 'active']);

            // Create savings account for the member
            if (!$member->savingsAccount) {
                SavingsAccount::create([
                    'member_id' => $member->id,
                    'account_number' => SavingsAccount::generateAccountNumber(),
                    'balance' => 0,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Member approved successfully',
                'member' => $member->load('user', 'savingsAccount'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve member: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a pending member registration
     */
    public function reject(Request $request, string $id)
    {
        $member = Member::with('user')->findOrFail($id);

        // Only super_admin and loan_officer can reject members
        if (!in_array(auth()->user()->role, ['super_admin', 'loan_officer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to reject members',
            ], 403);
        }

        if ($member->status !== 'inactive') {
            return response()->json([
                'success' => false,
                'message' => 'Only inactive members can be rejected',
            ], 400);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            // Store rejection reason in employment_info
            $employmentInfo = $member->employment_info ?? [];
            $employmentInfo['rejection_reason'] = $validated['reason'] ?? 'Application rejected by admin';
            $employmentInfo['rejected_at'] = now()->toIso8601String();
            $employmentInfo['rejected_by'] = auth()->user()->name;

            $member->update(['employment_info' => $employmentInfo]);

            // Delete the user account
            $member->user->delete();

            // Delete the member record
            $member->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Member application rejected successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject member: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get member financial summary.
     */
    public function summary(string $id)
    {
        $member = Member::with(['savingsAccount', 'loans', 'guarantorRecords'])->findOrFail($id);

        $this->authorize('view', $member);

        // Calculate total savings
        $totalSavings = $member->savingsAccount ? $member->savingsAccount->balance : 0;

        // Calculate active loans
        $activeLoans = $member->loans()->where('status', 'active')->get();
        $totalLoansAmount = $activeLoans->sum('amount');
        $totalOutstandingBalance = $activeLoans->sum('outstanding_balance');

        // Calculate guarantor exposure
        $guarantorExposure = $member->guarantorRecords()
            ->where('status', 'accepted')
            ->whereHas('loan', function ($query) {
                $query->where('status', 'active');
            })
            ->sum('guaranteed_amount');

        // Calculate available loan limit
        $maxLoanRatio = config('sacco.max_loan_to_savings_ratio', 3);
        $maxLoanAmount = $totalSavings * $maxLoanRatio;
        $availableLoanLimit = $maxLoanAmount - $totalOutstandingBalance;

        return response()->json([
            'member' => [
                'id' => $member->id,
                'member_number' => $member->member_number,
                'full_name' => $member->full_name,
                'status' => $member->status,
            ],
            'savings' => [
                'total_balance' => $totalSavings,
                'account_number' => $member->savingsAccount ? $member->savingsAccount->account_number : null,
            ],
            'loans' => [
                'active_loans_count' => $activeLoans->count(),
                'total_loans_amount' => $totalLoansAmount,
                'total_outstanding_balance' => $totalOutstandingBalance,
            ],
            'guarantor' => [
                'total_exposure' => $guarantorExposure,
                'active_guarantees_count' => $member->guarantorRecords()
                    ->where('status', 'accepted')
                    ->whereHas('loan', function ($query) {
                        $query->where('status', 'active');
                    })
                    ->count(),
            ],
            'loan_eligibility' => [
                'max_loan_amount' => $maxLoanAmount,
                'available_loan_limit' => max(0, $availableLoanLimit),
                'can_apply' => $availableLoanLimit > 0 && $member->isActive(),
            ],
        ]);
    }

    /**
     * Batch lookup members by member numbers
     */
    public function batchLookup(Request $request)
    {
        $validated = $request->validate([
            'member_numbers' => 'required|array',
            'member_numbers.*' => 'string',
        ]);

        $members = Member::whereIn('member_number', $validated['member_numbers'])
            ->select('member_number', 'first_name', 'last_name')
            ->get();

        $memberMap = [];
        foreach ($members as $member) {
            $memberMap[$member->member_number] = $member->first_name . ' ' . $member->last_name;
        }

        return response()->json([
            'members' => $memberMap,
        ]);
    }

    /**
     * Get current logged-in member's profile
     */
    public function currentMemberProfile(Request $request)
    {
        $user = $request->user();
        $member = Member::where('user_id', $user->id)->first();

        if (!$member) {
            return response()->json([
                'message' => 'Member profile not found',
            ], 404);
        }

        return response()->json([
            'member' => $member,
        ]);
    }

    /**
     * Get current logged-in member's financial summary
     */
    public function currentMemberSummary(Request $request)
    {
        $user = $request->user();
        $member = Member::where('user_id', $user->id)
            ->with(['savingsAccount', 'loans', 'guarantorRecords'])
            ->first();

        if (!$member) {
            return response()->json([
                'message' => 'Member profile not found',
            ], 404);
        }

        // Get savings account
        $savingsAccount = $member->savingsAccount;
        $savingsBalance = $savingsAccount ? $savingsAccount->balance : 0;

        // Get active loans
        $activeLoans = $member->loans()
            ->whereIn('status', ['approved', 'disbursed', 'active'])
            ->get();

        $totalLoansAmount = $activeLoans->sum('amount');
        $totalOutstandingBalance = $activeLoans->sum('outstanding_balance');

        // Get guarantor exposure
        $guarantorRecords = $member->guarantorRecords()
            ->where('status', 'accepted')
            ->with('loan')
            ->get();

        $totalGuarantorExposure = $guarantorRecords->sum(function ($record) {
            return $record->loan->outstanding_balance ?? 0;
        });

        // Calculate loan eligibility
        $maxLoanAmount = $savingsBalance * 3; // 3x savings
        $availableLoanLimit = $maxLoanAmount - $totalOutstandingBalance - $totalGuarantorExposure;
        $canApply = $availableLoanLimit > 0 && $member->status === 'active';

        return response()->json([
            'member' => [
                'id' => $member->id,
                'member_number' => $member->member_number,
                'full_name' => $member->first_name . ' ' . $member->last_name,
                'status' => $member->status,
            ],
            'savings' => [
                'total_balance' => $savingsBalance,
                'account_number' => $savingsAccount ? $savingsAccount->account_number : null,
            ],
            'loans' => [
                'active_loans_count' => $activeLoans->count(),
                'total_loans_amount' => $totalLoansAmount,
                'total_outstanding_balance' => $totalOutstandingBalance,
            ],
            'guarantor' => [
                'total_exposure' => $totalGuarantorExposure,
                'active_guarantees_count' => $guarantorRecords->count(),
            ],
            'loan_eligibility' => [
                'max_loan_amount' => $maxLoanAmount,
                'available_loan_limit' => $availableLoanLimit,
                'can_apply' => $canApply,
            ],
        ]);
    }

    /**
     * Update current logged-in member's profile
     */
    public function updateCurrentMember(Request $request)
    {
        $user = $request->user();
        $member = Member::where('user_id', $user->id)->first();

        if (!$member) {
            return response()->json([
                'message' => 'Member profile not found',
            ], 404);
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:500',
        ]);

        $member->update($validated);

        // Update user name if first_name or last_name changed
        if (isset($validated['first_name']) || isset($validated['last_name'])) {
            $user->update([
                'name' => ($validated['first_name'] ?? $member->first_name) . ' ' . 
                         ($validated['last_name'] ?? $member->last_name),
            ]);
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'member' => $member->fresh(),
        ]);
    }

    /**
     * Get loan eligibility for a member
     */
    public function loanEligibility(string $id)
    {
        $member = Member::with('savingsAccount', 'loans')->findOrFail($id);

        // Calculate savings balance
        $savingsBalance = $member->savingsAccount ? $member->savingsAccount->balance : 0;

        // Calculate max loan amount (3x savings)
        $maxLoanMultiplier = (float) config('sacco.max_loan_multiplier', 3);
        $maxLoanAmount = $savingsBalance * $maxLoanMultiplier;

        // Calculate existing active loans
        $activeLoansBalance = $member->loans()
            ->whereIn('status', ['active', 'approved_pending_disbursement', 'guarantors_approved'])
            ->sum('outstanding_balance');

        // Calculate guarantor exposure
        $guarantorExposure = $member->guarantorRecords()
            ->where('status', 'accepted')
            ->whereHas('loan', function ($q) {
                $q->whereIn('status', ['active', 'approved_pending_disbursement']);
            })
            ->sum('guaranteed_amount');

        // Available loan limit
        $availableLoanLimit = max(0, $maxLoanAmount - $activeLoansBalance);

        // Check eligibility conditions
        $errors = [];
        $canApply = true;

        if (!$member->isActive()) {
            $errors[] = 'Member account is not active';
            $canApply = false;
        }

        if (!$member->savingsAccount) {
            $errors[] = 'No savings account found';
            $canApply = false;
        }

        if ($savingsBalance <= 0) {
            $errors[] = 'Insufficient savings balance';
            $canApply = false;
        }

        if ($activeLoansBalance > 0) {
            $errors[] = 'Member has existing active loans';
            $canApply = false;
        }

        // Check minimum savings period
        if ($member->savingsAccount) {
            $minSavingsPeriodMonths = (int) config('sacco.min_savings_period_months', 6);
            $monthsSinceSavings = $member->savingsAccount->created_at->diffInMonths(now());
            
            if ($monthsSinceSavings < $minSavingsPeriodMonths) {
                $errors[] = "Member must have savings for at least {$minSavingsPeriodMonths} months";
                $canApply = false;
            }
        }

        return response()->json([
            'eligible' => $canApply,
            'max_loan_amount' => $maxLoanAmount,
            'available_loan_limit' => $availableLoanLimit,
            'savings_balance' => $savingsBalance,
            'active_loans_balance' => $activeLoansBalance,
            'guarantor_exposure' => $guarantorExposure,
            'can_apply' => $canApply && $availableLoanLimit > 0,
            'errors' => $errors,
        ]);
    }

    /**
     * Get potential guarantors for a member
     */
    public function potentialGuarantors(string $id)
    {
        $member = Member::findOrFail($id);

        // Get active members excluding the applicant
        $potentialGuarantors = Member::where('status', Member::STATUS_ACTIVE)
            ->where('id', '!=', $id)
            ->whereHas('savingsAccount', function ($query) {
                $query->where('balance', '>', 0);
            })
            ->with('savingsAccount')
            ->get()
            ->map(function ($guarantor) {
                // Calculate guarantor's capacity
                $existingGuarantees = $guarantor->guarantorRecords()
                    ->where('status', 'accepted')
                    ->whereHas('loan', function ($q) {
                        $q->whereIn('status', ['active', 'approved_pending_disbursement']);
                    })
                    ->sum('guaranteed_amount');

                $savingsBalance = $guarantor->savingsAccount ? $guarantor->savingsAccount->balance : 0;
                $maxGuaranteeCapacity = $savingsBalance * 2; // Can guarantee up to 2x their savings
                $availableCapacity = max(0, $maxGuaranteeCapacity - $existingGuarantees);

                return [
                    'id' => $guarantor->id,
                    'member_number' => $guarantor->member_number,
                    'full_name' => $guarantor->full_name,
                    'savings_balance' => $savingsBalance,
                    'existing_guarantees' => $existingGuarantees,
                    'available_capacity' => $availableCapacity,
                    'can_guarantee' => $availableCapacity > 0,
                ];
            })
            ->filter(fn($g) => $g['can_guarantee'])
            ->values();

        return response()->json([
            'data' => $potentialGuarantors,
        ]);
    }
}

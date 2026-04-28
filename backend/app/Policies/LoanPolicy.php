<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Loan;

class LoanPolicy
{
    /**
     * Determine if the user can view any loans.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view loans (filtered by role in controller)
    }

    /**
     * Determine if the user can view the loan.
     */
    public function view(User $user, Loan $loan): bool
    {
        return $user->isSuperAdmin() 
            || $user->isLoanOfficer() 
            || $user->isAccountant()
            || ($user->isMember() && $user->member && $user->member->id === $loan->member_id);
    }

    /**
     * Determine if the user can create loans.
     */
    public function create(User $user): bool
    {
        return $user->isMember() && $user->member;
    }

    /**
     * Determine if the user can update the loan.
     */
    public function update(User $user, Loan $loan): bool
    {
        // Only members can update their own pending loans
        return $user->isMember() 
            && $user->member 
            && $user->member->id === $loan->member_id 
            && $loan->status === 'pending';
    }

    /**
     * Determine if the user can approve loans.
     */
    public function approve(User $user, Loan $loan): bool
    {
        // Loan officers can approve loans under threshold
        // Super admin can approve all loans
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isLoanOfficer()) {
            $threshold = config('sacco.loan_approval_threshold', 5000000);
            return $loan->amount <= $threshold;
        }

        return false;
    }

    /**
     * Determine if the user can disburse loans.
     */
    public function disburse(User $user): bool
    {
        return $user->isAccountant() || $user->isSuperAdmin();
    }

    /**
     * Determine if the user can delete the loan.
     */
    public function delete(User $user, Loan $loan): bool
    {
        return $user->isSuperAdmin() && $loan->status === 'pending';
    }
}

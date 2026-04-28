<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Member;

class MemberPolicy
{
    /**
     * Determine if the user can view any members.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isLoanOfficer() || $user->isAccountant();
    }

    /**
     * Determine if the user can view the member.
     */
    public function view(User $user, Member $member): bool
    {
        return $user->isSuperAdmin() 
            || $user->isLoanOfficer() 
            || $user->isAccountant()
            || ($user->isMember() && $user->member && $user->member->id === $member->id);
    }

    /**
     * Determine if the user can create members.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can update the member.
     */
    public function update(User $user, Member $member): bool
    {
        return $user->isSuperAdmin() 
            || $user->isAccountant()
            || ($user->isMember() && $user->member && $user->member->id === $member->id);
    }

    /**
     * Determine if the user can delete the member.
     */
    public function delete(User $user, Member $member): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can change member status.
     */
    public function changeStatus(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}

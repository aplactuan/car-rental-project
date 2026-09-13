<?php

namespace App\Policies;

use App\Models\User;

class InvoicePolicy
{
    public function setPaymentStatus(User $user): bool
    {
        return $user->hasAdminPrivileges();
    }
}

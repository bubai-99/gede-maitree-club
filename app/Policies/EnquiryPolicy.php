<?php

namespace App\Policies;

use App\Models\Enquiry;
use App\Models\User;

/**
 * Messages from the website forms: any committee member may read them;
 * the Admin and the Website editor mark them as handled.
 */
class EnquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('console.view');
    }

    public function view(User $user, Enquiry $enquiry): bool
    {
        return $user->can('console.view');
    }

    public function update(User $user, Enquiry $enquiry): bool
    {
        return $user->can('website.edit');
    }
}

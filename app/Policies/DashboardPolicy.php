<?php

namespace App\Policies;

use App\Models\User;

class DashboardPolicy
{
    public function viewAny(User $user): bool
    {
        // Dashboard bütün autentifikasiya olunmuş istifadəçilər üçün açıqdır
        return true;
    }
}

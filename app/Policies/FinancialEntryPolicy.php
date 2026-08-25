<?php

namespace App\Policies;

use App\Models\FinancialEntry;
use App\Models\User;

class FinancialEntryPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('Super Admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('financial.manage');
    }

    public function view(User $user, FinancialEntry $entry): bool
    {
        return $user->can('financial.manage')
            && FinancialEntry::query()->visibleTo($user)->whereKey($entry->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('financial.manage');
    }

    public function update(User $user, FinancialEntry $entry): bool
    {
        return $this->view($user, $entry);
    }

    public function delete(User $user, FinancialEntry $entry): bool
    {
        return $this->view($user, $entry);
    }
}

<?php

namespace App\Policies;

use App\Models\HearingTranscription;
use App\Models\User;

class HearingTranscriptionPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('Super Admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('hearing-transcriptions.manage');
    }

    public function view(User $user, HearingTranscription $record): bool
    {
        return $user->can('hearing-transcriptions.manage')
            && HearingTranscription::query()->visibleTo($user)->whereKey($record->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('hearing-transcriptions.manage');
    }

    public function update(User $user, HearingTranscription $record): bool
    {
        return $this->view($user, $record);
    }
}

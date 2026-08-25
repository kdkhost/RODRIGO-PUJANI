<?php

namespace App\Policies;

use App\Models\SignatureRequest;
use App\Models\User;

class SignatureRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->enabled() && $user->can('signature-requests.view');
    }

    public function view(User $user, SignatureRequest $request): bool
    {
        return $this->enabled() && $user->can('signature-requests.view') && $request->legalDocument()->visibleTo($user)->exists();
    }

    public function create(User $user): bool
    {
        return $this->enabled() && $user->can('signature-requests.create');
    }

    public function manage(User $user, SignatureRequest $request): bool
    {
        return $this->enabled() && $user->can('signature-requests.manage') && $this->view($user, $request);
    }

    public function cancel(User $user, SignatureRequest $request): bool
    {
        return $this->enabled() && $user->can('signature-requests.cancel') && $this->view($user, $request);
    }

    public function download(User $user, SignatureRequest $request): bool
    {
        return $this->enabled() && $user->can('signature-requests.download') && $this->view($user, $request);
    }

    public function audit(User $user, SignatureRequest $request): bool
    {
        return $this->enabled() && $user->can('signature-requests.audit') && $this->view($user, $request);
    }

    private function enabled(): bool
    {
        return (bool) config('signatures.enabled', false);
    }
}

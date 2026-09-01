<?php

namespace App\Policies;

use App\Models\LegalDocument;
use App\Models\User;
use App\Services\ElectronicSignatureService;

class LegalDocumentPolicy
{
    public function view(User $user, LegalDocument $document): bool
    {
        return $user->can('legal-documents.manage')
            && LegalDocument::query()->visibleTo($user)->whereKey($document->id)->exists();
    }

    public function download(User $user, LegalDocument $document): bool
    {
        return $this->view($user, $document);
    }

    public function create(User $user): bool
    {
        return $user->can('legal-documents.manage');
    }

    public function update(User $user, LegalDocument $document): bool
    {
        return $this->view($user, $document);
    }

    public function delete(User $user, LegalDocument $document): bool
    {
        return $this->view($user, $document);
    }

    public function share(User $user, LegalDocument $document): bool
    {
        return $this->update($user, $document);
    }

    public function sendForSignature(User $user, LegalDocument $document): bool
    {
        return config('signatures.enabled', false)
            && $user->can('signature-requests.create')
            && ElectronicSignatureService::supports($document)
            && $this->view($user, $document);
    }

    public function viewSignatureEvidence(User $user, LegalDocument $document): bool
    {
        return config('signatures.enabled', false)
            && $user->can('signature-requests.audit')
            && $this->view($user, $document);
    }
}

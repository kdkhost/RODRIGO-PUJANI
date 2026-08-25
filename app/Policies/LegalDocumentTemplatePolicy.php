<?php

namespace App\Policies;

use App\Models\LegalDocumentTemplate;
use App\Models\User;

class LegalDocumentTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('legal-document-templates.view')
            || $user->can('legal-document-templates.manage');
    }

    public function view(User $user, LegalDocumentTemplate $template): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('legal-document-templates.manage');
    }

    public function update(User $user, LegalDocumentTemplate $template): bool
    {
        return $user->can('legal-document-templates.manage');
    }

    public function createVersion(User $user, LegalDocumentTemplate $template): bool
    {
        return $this->update($user, $template);
    }

    public function generate(User $user, LegalDocumentTemplate $template): bool
    {
        return $template->is_active
            && $user->can('legal-document-templates.generate')
            && $user->can('legal-documents.manage');
    }
}

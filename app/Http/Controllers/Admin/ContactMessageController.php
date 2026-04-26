<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ContactMessageController extends AdminCrudController
{
    protected string $modelClass = ContactMessage::class;
    protected string $viewPath = 'contact-messages';
    protected string $module = 'contact_messages';
    protected string $singularLabel = 'Mensagem';
    protected string $pluralLabel = 'Mensagens';
    protected string $routeBase = 'admin.contact-messages';
    protected array $searchable = ['name', 'email', 'phone', 'area_interest', 'message'];
    protected string $defaultSort = 'created_at';
    protected string $defaultDirection = 'desc';

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'area_interest' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'status' => ['nullable', 'in:new,in_progress,answered,archived'],
            'notes' => ['nullable', 'string'],
            'contacted_at' => ['nullable', 'date'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        $validated += $this->booleanData($request, ['consent']);
        $validated['status'] = $validated['status'] ?? 'new';

        return $validated;
    }
}

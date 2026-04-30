<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
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

    public function notifications(Request $request): JsonResponse
    {
        $since = $request->integer('since_id');
        $newQuery = ContactMessage::query()->where('status', 'new');

        $items = ContactMessage::query()
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(function (ContactMessage $message): array {
                return [
                    'id' => $message->id,
                    'name' => $message->name,
                    'email' => $message->email,
                    'phone' => $message->phone,
                    'area_interest' => $message->area_interest,
                    'subject' => $message->subject,
                    'status' => $message->status,
                    'status_label' => match ($message->status) {
                        'new' => 'Novo',
                        'in_progress' => 'Em andamento',
                        'answered' => 'Respondido',
                        'archived' => 'Arquivado',
                        default => ucfirst((string) $message->status),
                    },
                    'message_excerpt' => str($message->message)->stripTags()->squish()->limit(110)->toString(),
                    'created_at' => optional($message->created_at)->format('d/m/Y H:i'),
                    'manage_url' => route('admin.contact-messages.edit', $message->id),
                    'index_url' => route('admin.contact-messages.index'),
                ];
            })
            ->all();

        return response()->json([
            'unread_count' => (clone $newQuery)->count(),
            'new_count' => $since > 0 ? (clone $newQuery)->where('id', '>', $since)->count() : 0,
            'latest_id' => ContactMessage::query()->max('id') ?: 0,
            'items' => $items,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ClientController extends AdminCrudController
{
    protected string $modelClass = Client::class;
    protected string $viewPath = 'clients';
    protected string $module = 'clients';
    protected string $singularLabel = 'Cliente';
    protected string $pluralLabel = 'Clientes';
    protected string $routeBase = 'admin.clients';
    protected array $searchable = ['name', 'trade_name', 'document_number', 'email', 'phone', 'whatsapp'];
    protected string $defaultSort = 'name';
    protected string $defaultDirection = 'asc';

    protected function indexQuery(Request $request): Builder
    {
        return Client::query()
            ->visibleTo($request->user())
            ->with(['assignedLawyer:id,name']);
    }

    protected function formData(?Model $record = null): array
    {
        $lawyers = User::query()
            ->visibleTo(auth()->user())
            ->where('is_active', true)
            ->when(
                ! auth()->user()?->canViewAllLegalOperations(),
                fn (Builder $query) => $query->whereKey(auth()->id())
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'lawyers' => $lawyers,
            'canChooseLawyer' => auth()->user()?->canViewAllLegalOperations() ?? false,
            'portalEditableFieldOptions' => Client::portalEditableFieldOptions(),
        ];
    }

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'person_type' => ['required', 'in:individual,company'],
            'name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'alternate_phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date'],
            'profession' => ['nullable', 'string', 'max:255'],
            'address_zip' => ['nullable', 'string', 'max:12'],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:20'],
            'address_complement' => ['nullable', 'string', 'max:255'],
            'address_district' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'address_state' => ['nullable', 'string', 'size:2'],
            'notes' => ['nullable', 'string'],
            'assigned_lawyer_id' => ['nullable', 'integer', 'exists:users,id'],
            'portal_access_code' => [
                Rule::requiredIf(fn () => $request->boolean('portal_enabled') && blank($record?->portal_access_code)),
                'nullable',
                'string',
                'min:6',
                'max:32',
            ],
            'portal_notification_preference' => ['nullable', Rule::in(Client::PORTAL_NOTIFICATION_PREFERENCES)],
            'portal_editable_fields' => ['nullable', 'array'],
            'portal_editable_fields.*' => ['string', Rule::in(array_keys(Client::portalEditableFieldOptions()))],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        $validated += $this->booleanData($request, ['is_active', 'portal_enabled', 'portal_profile_update_allowed']);
        $validated['created_by'] ??= $record?->created_by ?: $request->user()?->id;
        $validated['address_state'] = filled($validated['address_state'] ?? null)
            ? strtoupper((string) $validated['address_state'])
            : null;

        if (! $request->user()?->canViewAllLegalOperations()) {
            $validated['assigned_lawyer_id'] = $request->user()->id;
        }

        if (filled($validated['portal_access_code'] ?? null)) {
            $validated['portal_access_code'] = Hash::make((string) $validated['portal_access_code']);
            $validated['portal_access_code_updated_at'] = now();
        } else {
            unset($validated['portal_access_code']);
        }

        if (! $validated['portal_enabled']) {
            $validated['portal_profile_update_allowed'] = false;
            $validated['portal_access_code'] = null;
            $validated['portal_access_code_updated_at'] = null;
            $validated['portal_last_login_at'] = null;
            $validated['portal_last_login_ip'] = null;
        }

        $editableFieldOptions = array_keys(Client::portalEditableFieldOptions());
        $metadata = is_array($record?->metadata) ? $record->metadata : [];
        $metadata['portal_notification_preference'] = (string) ($validated['portal_notification_preference'] ?? ($metadata['portal_notification_preference'] ?? 'both'));
        $selectedEditableFields = $request->has('portal_editable_fields')
            ? array_map('strval', (array) ($validated['portal_editable_fields'] ?? []))
            : [];
        $metadata['portal_editable_fields'] = array_values(array_intersect(
            $editableFieldOptions,
            $selectedEditableFields
        ));

        $validated['metadata'] = $metadata;
        unset($validated['portal_notification_preference'], $validated['portal_editable_fields']);

        return $validated;
    }

    protected function resolveRecord(string $record): Model
    {
        return Client::query()
            ->with(['assignedLawyer:id,name'])
            ->visibleTo(auth()->user())
            ->findOrFail($record);
    }
}

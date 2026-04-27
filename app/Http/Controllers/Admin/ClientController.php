<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

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
        $query = Client::query()->with(['assignedLawyer:id,name']);

        if ($request->user()?->isAssociatedLawyer()) {
            $query->where('assigned_lawyer_id', $request->user()->id);
        }

        return $query;
    }

    protected function formData(?Model $record = null): array
    {
        $lawyers = User::query()
            ->where('is_active', true)
            ->when(
                auth()->user()?->isAssociatedLawyer(),
                fn (Builder $query) => $query->whereKey(auth()->id())
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'lawyers' => $lawyers,
            'canChooseLawyer' => ! auth()->user()?->isAssociatedLawyer(),
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
            'address_state' => ['nullable', 'string', 'max:8'],
            'notes' => ['nullable', 'string'],
            'assigned_lawyer_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        $validated += $this->booleanData($request, ['is_active']);
        $validated['created_by'] ??= $record?->created_by ?: $request->user()?->id;

        if ($request->user()?->isAssociatedLawyer()) {
            $validated['assigned_lawyer_id'] = $request->user()->id;
        }

        return $validated;
    }

    protected function resolveRecord(string $record): Model
    {
        return Client::query()
            ->with(['assignedLawyer:id,name'])
            ->when(
                auth()->user()?->isAssociatedLawyer(),
                fn (Builder $query) => $query->where('assigned_lawyer_id', auth()->id())
            )
            ->findOrFail($record);
    }
}

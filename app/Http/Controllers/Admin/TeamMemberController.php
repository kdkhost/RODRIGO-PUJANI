<?php

namespace App\Http\Controllers\Admin;

use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamMemberController extends AdminCrudController
{
    protected string $modelClass = TeamMember::class;
    protected string $viewPath = 'team-members';
    protected string $module = 'team_members';
    protected string $singularLabel = 'Membro';
    protected string $pluralLabel = 'Equipe';
    protected string $routeBase = 'admin.team-members';
    protected array $searchable = ['name', 'slug', 'role', 'oab_number'];
    protected string $defaultSort = 'sort_order';
    protected string $defaultDirection = 'asc';

    protected function indexQuery(Request $request): Builder
    {
        return TeamMember::query()->with([
            'linkedUser' => fn ($query) => $query->visibleTo($request->user())->with('roles'),
        ]);
    }

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', $this->uniqueRule('team_members', 'slug', $record)],
            'role' => ['required', 'string', 'max:255'],
            'oab_number' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'specialties_text' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'linkedin_url' => ['nullable', 'url'],
            'instagram_url' => ['nullable', 'url'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        unset($validated['image'], $validated['specialties_text']);
        $validated += $this->booleanData($request, ['is_partner', 'is_active']);
        $validated['specialties'] = collect(explode(',', (string) $request->input('specialties_text')))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();
        $validated['image_path'] = $this->storeMediaFile($request, 'image', 'team', $record?->image_path);

        return $validated;
    }

    public function toggleActive(Request $request, string $record): JsonResponse
    {
        /** @var TeamMember $entity */
        $entity = $this->resolveRecord($record);

        $entity->forceFill([
            'is_active' => ! $entity->is_active,
        ])->save();

        activity_log(
            $this->module,
            $entity->is_active ? 'activated' : 'deactivated',
            $entity,
            ['is_active' => $entity->is_active],
            $entity->is_active ? 'Profissional ativado.' : 'Profissional desativado.'
        );

        return response()->json([
            'message' => $entity->is_active ? 'Profissional ativado com sucesso.' : 'Profissional desativado com sucesso.',
            'tableTarget' => '#admin-resource-table',
        ]);
    }
}

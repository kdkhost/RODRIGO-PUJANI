<?php

namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SettingController extends AdminCrudController
{
    protected string $modelClass = Setting::class;
    protected string $viewPath = 'settings';
    protected string $module = 'settings';
    protected string $singularLabel = 'Configuração';
    protected string $pluralLabel = 'Configurações';
    protected string $routeBase = 'admin.settings';
    protected array $searchable = ['group', 'key', 'label', 'value'];
    protected string $defaultSort = 'sort_order';
    protected string $defaultDirection = 'asc';

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'group' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:255', $this->uniqueRule('settings', 'key', $record)],
            'label' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'value' => ['nullable', 'string'],
            'json_text' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    if ($request->input('type') !== 'json' || blank($value)) {
                        return;
                    }

                    json_decode((string) $value, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $fail('O JSON informado nao e valido.');
                    }
                },
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        $validated += $this->booleanData($request, ['is_public']);
        $validated['json_value'] = $validated['type'] === 'json' && filled($request->input('json_text'))
            ? json_decode((string) $request->input('json_text'), true)
            : null;
        unset($validated['json_text']);

        return $validated;
    }
}

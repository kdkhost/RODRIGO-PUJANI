<?php

namespace App\Http\Controllers\Admin;

use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaAssetController extends AdminCrudController
{
    protected string $modelClass = MediaAsset::class;
    protected string $viewPath = 'media-assets';
    protected string $module = 'media_assets';
    protected string $singularLabel = 'Mídia';
    protected string $pluralLabel = 'Biblioteca de Mídia';
    protected string $routeBase = 'admin.media-assets';
    protected array $searchable = ['original_name', 'file_name', 'alt_text', 'caption'];
    protected string $defaultSort = 'created_at';
    protected string $defaultDirection = 'desc';

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'directory' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string'],
            'file' => [$record?->exists ? 'nullable' : 'required', 'file', 'max:10240'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        $directory = $validated['directory'] ?: 'media';

        if ($request->hasFile('file')) {
            if ($record?->path && Storage::disk('public')->exists($record->path)) {
                Storage::disk('public')->delete($record->path);
            }

            $path = $this->storeMediaFile($request, 'file', $directory, null);
            $file = $request->file('file');

            $validated['original_name'] = $file->getClientOriginalName();
            $validated['file_name'] = basename($path);
            $validated['disk'] = 'public';
            $validated['directory'] = $directory;
            $validated['path'] = $path;
            $validated['extension'] = $file->getClientOriginalExtension();
            $validated['mime_type'] = $file->getMimeType();
            $validated['size'] = $file->getSize();
            $validated['type'] = Str::startsWith((string) $file->getMimeType(), 'image/') ? 'image' : 'file';
            $validated['uploaded_by'] = auth()->id();
        }

        unset($validated['file']);
        $validated += $this->booleanData($request, ['is_public']);

        return $validated;
    }

    protected function beforeDelete(Model $record): void
    {
        if ($record->path && Storage::disk('public')->exists($record->path)) {
            Storage::disk('public')->delete($record->path);
        }
    }
}

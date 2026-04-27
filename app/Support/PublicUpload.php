<?php

namespace App\Support;

use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicUpload
{
    public static function store(
        UploadedFile $file,
        string $directory,
        ?string $currentPath = null,
        ?int $uploadedBy = null,
        bool $registerAsset = true,
    ): string {
        self::delete($currentPath);

        $directory = trim($directory, '/');
        $publicDirectory = 'uploads'.($directory !== '' ? '/'.$directory : '');
        $absoluteDirectory = public_path($publicDirectory);

        File::ensureDirectoryExists($absoluteDirectory, 0755, true);

        $originalName = $file->getClientOriginalName();
        $extension = Str::lower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        $fileName = now()->format('YmdHis').'-'.Str::random(16).'.'.$extension;

        $file->move($absoluteDirectory, $fileName);

        $path = $publicDirectory.'/'.$fileName;

        if ($registerAsset) {
            MediaAsset::query()->create([
                'original_name' => $originalName,
                'file_name' => $fileName,
                'disk' => 'public',
                'directory' => $directory,
                'path' => $path,
                'extension' => $extension,
                'mime_type' => $mimeType,
                'size' => $size,
                'type' => Str::startsWith((string) $mimeType, 'image/') ? 'image' : 'file',
                'uploaded_by' => $uploadedBy,
                'is_public' => true,
            ]);
        }

        return $path;
    }

    public static function delete(?string $path): void
    {
        if (! filled($path) || Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        $normalized = ltrim($path, '/');

        if (Str::startsWith($normalized, 'uploads/')) {
            $publicPath = public_path($normalized);

            if (File::exists($publicPath)) {
                File::delete($publicPath);
            }

            return;
        }

        if (Storage::disk('public')->exists($normalized)) {
            Storage::disk('public')->delete($normalized);
        }
    }
}

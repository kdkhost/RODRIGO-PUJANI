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
        $fileName = now()->format('YmdHis').'-'.Str::random(16).'.'.$extension;
        $targetPath = $absoluteDirectory.'/'.$fileName;

        if (! self::optimizeImageTo($file, $targetPath, $extension)) {
            $file->move($absoluteDirectory, $fileName);
        }

        $path = $publicDirectory.'/'.$fileName;
        $size = is_file($targetPath) ? (filesize($targetPath) ?: $file->getSize()) : $file->getSize();
        $mimeType = is_file($targetPath) ? (File::mimeType($targetPath) ?: $mimeType) : $mimeType;

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

    private static function optimizeImageTo(UploadedFile $file, string $targetPath, string $extension): bool
    {
        $source = $file->getRealPath();
        $extension = Str::lower($extension);

        if (! is_string($source) || ! is_file($source) || ! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return false;
        }

        try {
            $image = match ($extension) {
                'jpg', 'jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($source) : false,
                'png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($source) : false,
                'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
                default => false,
            };

            if (! $image) {
                return false;
            }

            if (in_array($extension, ['png', 'webp'], true)) {
                imagepalettetotruecolor($image);
                imagealphablending($image, false);
                imagesavealpha($image, true);
            }

            $written = match ($extension) {
                'jpg', 'jpeg' => imagejpeg($image, $targetPath, 82),
                'png' => imagepng($image, $targetPath, 8),
                'webp' => function_exists('imagewebp') ? imagewebp($image, $targetPath, 82) : false,
                default => false,
            };

            imagedestroy($image);

            if (! $written || ! is_file($targetPath) || filesize($targetPath) <= 0) {
                @unlink($targetPath);

                return false;
            }

            if (filesize($targetPath) > filesize($source)) {
                @unlink($targetPath);

                return false;
            }

            return true;
        } catch (\Throwable) {
            @unlink($targetPath);

            return false;
        }
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

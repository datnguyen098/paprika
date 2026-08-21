<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class UploadService
{
    public function uploadImage(?UploadedFile $file, string $folder, ?string $profile = null): ?string
    {
        if (! $file) {
            return null;
        }

        if ($this->isSvg($file)) {
            return $file->store($folder, $this->disk());
        }

        return $this->storeOptimizedRasterImage($file, $folder, $profile);
    }

    public function uploadMultipleImages(?array $files, string $folder): array
    {
        if (empty($files)) {
            return [];
        }

        return collect($files)
            ->filter(fn ($file) => $file instanceof UploadedFile)
            ->map(fn (UploadedFile $file): string => $this->uploadImage($file, $folder))
            ->values()
            ->all();
    }

    public function deleteImage(?string $path): void
    {
        if (blank($path) || str_starts_with($path, '/') || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        Storage::disk($this->disk())->delete($path);
        Storage::disk($this->disk())->delete($this->variantPaths($path));
    }

    public function deleteImages(array $paths): void
    {
        foreach ($paths as $path) {
            $this->deleteImage($path);
        }
    }

    private function storeOptimizedRasterImage(UploadedFile $file, string $folder, ?string $profile = null): string
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            return $this->storeOriginalImage($file, $folder);
        }

        $imageInfo = getimagesize($file->getRealPath());

        if (! $imageInfo) {
            return $this->storeOriginalImage($file, $folder);
        }

        [$width, $height, $type] = $imageInfo;

        $source = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($file->getRealPath()),
            IMAGETYPE_PNG => imagecreatefrompng($file->getRealPath()),
            IMAGETYPE_WEBP => imagecreatefromwebp($file->getRealPath()),
            default => null,
        };

        if (! $source) {
            return $this->storeOriginalImage($file, $folder);
        }

        $profileConfig = $this->profileFor($folder, $profile);
        $maxWidth = max(1, (int) ($profileConfig['width'] ?? config('uploads.resize_width', 1600)));
        $maxHeight = max(1, (int) ($profileConfig['height'] ?? config('uploads.resize_height', 1600)));
        $quality = max(1, min(100, (int) ($profileConfig['quality'] ?? config('uploads.webp_quality', 84))));
        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagepalettetotruecolor($source);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $path = trim($folder, '/').'/'.Str::uuid().'.webp';
        $this->ensureStorageDirectory($folder);
        $stored = imagewebp($target, Storage::disk($this->disk())->path($path), $quality);

        if ($stored) {
            $this->storeVariantsFromResource($target, $targetWidth, $targetHeight, $path, $profileConfig['name'] ?? $this->profileNameFor($folder, $profile));
        }

        imagedestroy($source);
        imagedestroy($target);

        return $stored ? $path : $this->storeOriginalImage($file, $folder);
    }

    private function storeOriginalImage(UploadedFile $file, string $folder): string
    {
        $this->ensureStorageDirectory($folder);

        $path = $file->store($folder, $this->disk());

        if (! is_string($path) || blank($path)) {
            throw new RuntimeException('Khong the luu anh. Hay kiem tra quyen ghi cua disk upload ['.$this->disk().'].');
        }

        return $path;
    }

    private function ensureStorageDirectory(string $folder): void
    {
        if (! Storage::disk($this->disk())->makeDirectory($folder)) {
            throw new RuntimeException("Khong the tao thu muc upload [{$folder}] tren disk [{$this->disk()}].");
        }

        $absoluteFolder = Storage::disk($this->disk())->path($folder);

        if (! is_dir($absoluteFolder) || ! is_writable($absoluteFolder)) {
            throw new RuntimeException("Thu muc upload [{$absoluteFolder}] khong co quyen ghi.");
        }
    }

    public function regenerateResponsiveVariants(?string $path, ?string $profile = null, bool $overwrite = true): int
    {
        if (! $this->isLocalRasterPath($path) || ! $this->imageExists($path)) {
            return 0;
        }

        $absolutePath = $this->absoluteImagePath($path);
        $imageInfo = getimagesize($absolutePath);

        if (! $imageInfo) {
            return 0;
        }

        [$width, $height, $type] = $imageInfo;
        $source = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($absolutePath),
            IMAGETYPE_PNG => imagecreatefrompng($absolutePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($absolutePath),
            default => null,
        };

        if (! $source) {
            return 0;
        }

        imagepalettetotruecolor($source);
        imagealphablending($source, true);
        imagesavealpha($source, true);

        $count = $this->storeVariantsFromResource($source, $width, $height, $path, $profile ?: $this->profileNameFor(dirname($path), null), $overwrite);
        imagedestroy($source);

        return $count;
    }

    private function profileFor(string $folder, ?string $profile = null): array
    {
        $profiles = config('uploads.profiles', []);
        $profileName = $this->profileNameFor($folder, $profile);

        $config = $profiles[$profileName] ?? $profiles['default'] ?? [
            'width' => config('uploads.resize_width', 1600),
            'height' => config('uploads.resize_height', 1600),
            'quality' => config('uploads.webp_quality', 84),
        ];
        $config['name'] = $profileName;

        return $config;
    }

    private function profileNameFor(string $folder, ?string $profile = null): string
    {
        $folderProfiles = config('uploads.folder_profiles', []);

        return $profile ?: ($folderProfiles[trim($folder, '/')] ?? 'default');
    }

    private function storeVariantsFromResource($source, int $width, int $height, string $path, ?string $profileName = null, bool $overwrite = false): int
    {
        $variants = config('uploads.variants', []);
        $profileVariants = config('uploads.variant_profiles.'.($profileName ?: 'default'), ['thumb', 'card', 'large']);
        $storedCount = 0;

        foreach ($profileVariants as $variantName) {
            if (! isset($variants[$variantName])) {
                continue;
            }

            $variantPath = $this->variantPath($path, $variantName);

            if (! $overwrite && $this->imageExists($variantPath)) {
                continue;
            }

            $variantWidth = min($width, max(1, (int) $variants[$variantName]['width']));
            $ratio = $variantWidth / $width;
            $variantHeight = max(1, (int) round($height * $ratio));
            $quality = max(1, min(100, (int) ($variants[$variantName]['quality'] ?? config('uploads.webp_quality', 84))));

            $target = imagecreatetruecolor($variantWidth, $variantHeight);
            imagealphablending($target, false);
            imagesavealpha($target, true);
            imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
            imagecopyresampled($target, $source, 0, 0, 0, 0, $variantWidth, $variantHeight, $width, $height);

            $this->ensureImageDirectory($variantPath);
            $stored = imagewebp($target, $this->absoluteImagePath($variantPath), $quality);
            imagedestroy($target);

            if ($stored) {
                $storedCount++;
            }
        }

        return $storedCount;
    }

    private function variantPaths(string $path): array
    {
        return collect(array_keys(config('uploads.variants', [])))
            ->map(fn (string $variant): string => $this->variantPath($path, $variant))
            ->all();
    }

    private function variantPath(string $path, string $variant): string
    {
        $isPublicPath = str_starts_with($path, '/');
        $directory = trim(dirname($path), '.\\/');
        $filename = pathinfo($path, PATHINFO_FILENAME).'-'.$variant.'.webp';

        return ($isPublicPath ? '/' : '').($directory ? trim($directory, '/').'/' : '').$filename;
    }

    private function isLocalRasterPath(?string $path): bool
    {
        if (blank($path) || str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_ends_with(strtolower($path), '.svg')) {
            return false;
        }

        return true;
    }

    private function imageExists(string $path): bool
    {
        return str_starts_with($path, '/')
            ? file_exists(public_path(ltrim($path, '/')))
            : Storage::disk($this->disk())->exists($path);
    }

    private function absoluteImagePath(string $path): string
    {
        return str_starts_with($path, '/')
            ? public_path(ltrim($path, '/'))
            : Storage::disk($this->disk())->path($path);
    }

    private function ensureImageDirectory(string $path): void
    {
        if (str_starts_with($path, '/')) {
            $directory = dirname(public_path(ltrim($path, '/')));

            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            return;
        }

        $this->ensureStorageDirectory(dirname($path));
    }

    private function isSvg(UploadedFile $file): bool
    {
        return $file->getClientOriginalExtension() === 'svg'
            || $file->getMimeType() === 'image/svg+xml';
    }

    private function disk(): string
    {
        return config('uploads.disk', 'public');
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CheckStorageSetup extends Command
{
    protected $signature = 'app:check-storage {--write : Write and delete a temporary test file}';

    protected $description = 'Check production storage permissions, public symlink and image processing support.';

    public function handle(): int
    {
        $disk = config('uploads.disk', 'public');
        $uploadRoot = Storage::disk($disk)->path('');
        $publicLink = public_path('storage');
        $resolvedPublicLink = realpath($publicLink) ?: null;

        $checks = [
            ['Upload disk', $disk, true],
            ['Upload path', $uploadRoot, is_dir($uploadRoot)],
            ['Upload writable', $uploadRoot, is_writable($uploadRoot)],
            ['Public storage link exists', $publicLink, file_exists($publicLink)],
            ['Public storage is accessible', $publicLink, is_link($publicLink) || is_dir($publicLink) || ($resolvedPublicLink && is_dir($resolvedPublicLink))],
            ['GD extension', 'extension_loaded("gd")', extension_loaded('gd')],
            ['WebP support', 'function_exists("imagewebp")', function_exists('imagewebp')],
        ];

        foreach ($checks as [$label, $value, $passed]) {
            $this->line(sprintf(
                '%s %s: %s',
                $passed ? '<info>PASS</info>' : '<error>FAIL</error>',
                $label,
                $value
            ));
        }

        if ($this->option('write')) {
            $path = 'health-check/'.Str::uuid().'.txt';
            $written = Storage::disk($disk)->put($path, 'ok');
            $exists = Storage::disk($disk)->exists($path);

            $this->line(sprintf(
                '%s Write test: %s',
                $written && $exists ? '<info>PASS</info>' : '<error>FAIL</error>',
                $path
            ));

            Storage::disk($disk)->delete($path);
        }

        $this->newLine();
        $this->line('Public URL sample: '.Storage::disk($disk)->url('health-check/example.txt'));

        return self::SUCCESS;
    }
}

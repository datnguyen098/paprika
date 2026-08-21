<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Dish;
use App\Models\GalleryImage;
use App\Models\Page;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\User;
use App\Services\UploadService;
use Illuminate\Console\Command;

class RegenerateResponsiveImages extends Command
{
    protected $signature = 'media:regenerate-responsive-images {--force : Ghi đè thumbnail đã có}';

    protected $description = 'Tạo lại thumbnail WebP nhiều kích thước cho ảnh đã upload.';

    public function handle(UploadService $uploads): int
    {
        $totalImages = 0;
        $totalVariants = 0;

        foreach ($this->imagePaths() as [$path, $profile]) {
            $created = $uploads->regenerateResponsiveVariants($path, $profile, (bool) $this->option('force'));

            if ($created > 0) {
                $totalImages++;
                $totalVariants += $created;
                $this->line("Generated {$created} variants: {$path}");
            }
        }

        $this->info("Done. {$totalVariants} variants generated for {$totalImages} images.");

        return self::SUCCESS;
    }

    private function imagePaths(): array
    {
        $paths = [];

        Banner::query()->pluck('image')->each(fn ($path) => $paths[] = [$path, 'hero']);
        Promotion::query()->pluck('image')->each(fn ($path) => $paths[] = [$path, 'hero']);
        GalleryImage::query()->pluck('image')->each(fn ($path) => $paths[] = [$path, 'hero']);
        Category::query()->pluck('image')->each(fn ($path) => $paths[] = [$path, 'content']);
        Page::query()->pluck('image')->each(fn ($path) => $paths[] = [$path, 'content']);
        Post::query()->pluck('thumbnail')->each(fn ($path) => $paths[] = [$path, 'content']);
        Testimonial::query()->pluck('avatar')->each(fn ($path) => $paths[] = [$path, 'avatar']);
        User::query()->pluck('avatar')->each(fn ($path) => $paths[] = [$path, 'avatar']);

        Dish::query()->get(['image', 'gallery'])->each(function (Dish $dish) use (&$paths): void {
            $paths[] = [$dish->image, 'content'];

            foreach (($dish->gallery ?: []) as $path) {
                $paths[] = [$path, 'content'];
            }
        });

        SiteSetting::query()
            ->whereIn('key', ['logo_header', 'logo_footer', 'favicon', 'og_image', 'default_background'])
            ->get(['key', 'value'])
            ->each(function (SiteSetting $setting) use (&$paths): void {
                $profile = in_array($setting->key, ['logo_header', 'logo_footer', 'favicon'], true) ? 'brand' : 'hero';
                $paths[] = [$setting->value, $profile];
            });

        return collect($paths)
            ->filter(fn (array $item): bool => filled($item[0]))
            ->unique(fn (array $item): string => $item[0].'|'.$item[1])
            ->values()
            ->all();
    }
}

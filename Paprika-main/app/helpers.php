<?php

use App\Models\Branch;
use App\Models\Dish;
use App\Models\SiteSetting;
use App\Support\DishAvailabilityService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        $settings = Cache::rememberForever('site_settings', fn () => SiteSetting::query()
            ->pluck('value', 'key')
            ->all());

        return $settings[$key] ?? $default;
    }
}

if (! function_exists('media_url')) {
    function media_url(?string $path, ?string $default = null): ?string
    {
        if (blank($path)) {
            return $default;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk(config('uploads.disk', 'public'))->url($path);
    }
}

if (! function_exists('media_variant_path')) {
    function media_variant_path(?string $path, string $variant): ?string
    {
        if (blank($path) || str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:') || str_ends_with(strtolower($path), '.svg')) {
            return $path;
        }

        $isPublicPath = str_starts_with($path, '/');
        $directory = trim(dirname($path), '.\\/');
        $variantPath = ($isPublicPath ? '/' : '').($directory ? trim($directory, '/').'/' : '').pathinfo($path, PATHINFO_FILENAME).'-'.$variant.'.webp';

        $exists = $isPublicPath
            ? file_exists(public_path(ltrim($variantPath, '/')))
            : Storage::disk(config('uploads.disk', 'public'))->exists($variantPath);

        return $exists ? $variantPath : $path;
    }
}

if (! function_exists('media_variant_url')) {
    function media_variant_url(?string $path, string $variant, ?string $default = null): ?string
    {
        return media_url(media_variant_path($path, $variant), $default);
    }
}

if (! function_exists('media_srcset')) {
    function media_srcset(?string $path, array|string $variants = ['thumb', 'card', 'large']): ?string
    {
        if (blank($path) || str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:') || str_ends_with(strtolower($path), '.svg')) {
            return null;
        }

        $variants = is_array($variants) ? $variants : explode(',', $variants);
        $config = config('uploads.variants', []);

        return collect($variants)
            ->map(function (string $variant) use ($path, $config): ?string {
                $variant = trim($variant);
                $variantPath = media_variant_path($path, $variant);

                if ($variantPath === $path || ! isset($config[$variant]['width'])) {
                    return null;
                }

                return media_url($variantPath).' '.(int) $config[$variant]['width'].'w';
            })
            ->filter()
            ->implode(', ') ?: null;
    }
}

if (! function_exists('localized_setting')) {
    function localized_setting(string $key, mixed $default = null): mixed
    {
        if (! is_default_locale()) {
            $localeValue = setting($key.'_'.current_locale());

            if (filled($localeValue)) {
                return $localeValue;
            }
        }

        return setting($key, $default);
    }
}

if (! function_exists('primary_branch')) {
    function primary_branch(): ?Branch
    {
        $branchId = Cache::rememberForever('primary_branch_id', fn () => Branch::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->value('id'));

        return $branchId ? Branch::query()->find($branchId) : null;
    }
}

if (! function_exists('branch_value')) {
    function branch_value(string $field, mixed $default = null): mixed
    {
        return primary_branch()?->{$field} ?: $default;
    }
}

if (! function_exists('business_timezone')) {
    function business_timezone(?Branch $branch = null): string
    {
        $fallback = 'Europe/Athens';
        $timezone = $branch?->timezone ?: setting('business_timezone', $fallback);

        try {
            new DateTimeZone((string) $timezone);

            return (string) $timezone;
        } catch (Throwable) {
            return $fallback;
        }
    }
}

if (! function_exists('business_time')) {
    function business_time(mixed $date, ?Branch $branch = null): ?\Illuminate\Support\Carbon
    {
        if (! $date) {
            return null;
        }

        $carbon = $date instanceof \Carbon\CarbonInterface
            ? $date->copy()
            : \Illuminate\Support\Carbon::parse($date);

        return $carbon->timezone(business_timezone($branch));
    }
}

if (! function_exists('business_now')) {
    function business_now(?Branch $branch = null): \Illuminate\Support\Carbon
    {
        return now(business_timezone($branch));
    }
}

if (! function_exists('business_today')) {
    function business_today(?Branch $branch = null): \Illuminate\Support\Carbon
    {
        return business_now($branch)->startOfDay();
    }
}

if (! function_exists('branch_map_query')) {
    function branch_map_query(?Branch $branch = null): string
    {
        $branch ??= primary_branch();

        if (! $branch) {
            return 'Paprika Patras Greece';
        }

        if (filled($branch->delivery_origin_latitude) && filled($branch->delivery_origin_longitude)) {
            return $branch->delivery_origin_latitude.','.$branch->delivery_origin_longitude;
        }

        return collect([$branch->address, $branch->name, $branch->city ?: 'Patras', 'Greece'])
            ->filter(fn ($value) => filled($value))
            ->implode(', ');
    }
}

if (! function_exists('branch_map_embed_url')) {
    function branch_map_embed_url(?Branch $branch = null): string
    {
        $branch ??= primary_branch();

        if ($branch && filled($branch->google_map_iframe) && preg_match('/src=["\']([^"\']+)["\']/i', $branch->google_map_iframe, $matches)) {
            $src = html_entity_decode($matches[1], ENT_QUOTES);

            if (str_starts_with($src, 'https://www.google.com/maps') || str_starts_with($src, 'https://maps.google.')) {
                return $src;
            }
        }

        return 'https://www.google.com/maps?q='.rawurlencode(branch_map_query($branch)).'&output=embed';
    }
}

if (! function_exists('branch_map_directions_url')) {
    function branch_map_directions_url(?Branch $branch = null): string
    {
        return 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode(branch_map_query($branch));
    }
}

if (! function_exists('show_dish_prices')) {
    function show_dish_prices(): bool
    {
        return (string) setting('show_dish_prices', '1') === '1';
    }
}

if (! function_exists('active_branch_id')) {
    function active_branch_id(): ?int
    {
        $request = request();
        $branchId = $request->attributes->get('branch_id');

        if (! $branchId && $request->hasSession()) {
            $branchId = $request->session()->get('active_branch_id');
        }

        if ($branchId) {
            return (int) $branchId;
        }

        return primary_branch()?->id;
    }
}

if (! function_exists('active_branch')) {
    function active_branch(): ?Branch
    {
        $id = active_branch_id();

        return $id ? Branch::query()->find($id) : null;
    }
}

if (! function_exists('dish_availability')) {
    function dish_availability(Dish $dish, ?Branch $branch = null): \App\Support\DishAvailabilityResult
    {
        $branch ??= $branch ?: active_branch() ?: primary_branch();

        if (! $branch) {
            return new \App\Support\DishAvailabilityResult(true, collect(), collect());
        }

        return app(DishAvailabilityService::class)->check($dish, $branch);
    }
}

if (! function_exists('is_dish_available_now')) {
    function is_dish_available_now(Dish $dish, ?Branch $branch = null): bool
    {
        return dish_availability($dish, $branch)->available;
    }
}

if (! function_exists('format_money')) {
    function format_money(int|float|string|null $amount, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?: (string) setting('currency_code', 'EUR'));
        $amount = (int) $amount;

        if ($currency === 'EUR') {
            return '€'.number_format($amount / 100, 2, ',', '.');
        }

        return number_format($amount, 0, ',', '.').' '.$currency;
    }
}

if (! function_exists('current_locale')) {
    function current_locale(): string
    {
        return app()->getLocale() ?: config('locales.default', 'vi');
    }
}

if (! function_exists('is_english')) {
    function is_english(): bool
    {
        return current_locale() === 'en';
    }
}

if (! function_exists('is_default_locale')) {
    function is_default_locale(): bool
    {
        return current_locale() === config('locales.default', 'vi');
    }
}

if (! function_exists('localized_route')) {
    function localized_route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        $locale = current_locale();
        $localizedName = "localized.{$locale}.{$name}";

        if (\Illuminate\Support\Facades\Route::has($localizedName)) {
            // Get the target route's parameter names
            $targetRoute = \Illuminate\Support\Facades\Route::getRoutes()->getByName($localizedName);
            $targetParamNames = $targetRoute?->parameterNames() ?? [];
            
            if (is_array($parameters)) {
                // Map 'dish' -> 'slug', 'post' -> 'slug', 'page' -> 'slug' based on target route
                $mappings = [
                    'menu.show' => ['dish' => 'slug'],
                    'blog.show' => ['post' => 'slug'],
                    'pages.show' => ['slug' => 'page'],
                ];
                
                if (isset($mappings[$name])) {
                    foreach ($mappings[$name] as $from => $to) {
                        if (isset($parameters[$from]) && !isset($parameters[$to])) {
                            $parameters[$to] = $parameters[$from];
                            unset($parameters[$from]);
                        }
                    }
                }
            }

            return route($localizedName, $parameters, $absolute);
        }

        // Fallback to regular route if localized doesn't exist
        return route($name, $parameters, $absolute);
    }
}

if (! function_exists('localized_url')) {
    function localized_url(string $path): string
    {
        $path = '/'.ltrim($path, '/');
        $locale = current_locale();
        $prefix = config("locales.supported.{$locale}.prefix", $locale);

        return url('/'.$prefix.($path === '/' ? '' : $path));
    }
}

if (! function_exists('localized_field')) {
    function localized_field(object $model, string $field, mixed $default = null): mixed
    {
        if (method_exists($model, 'localized')) {
            return $model->localized($field, $default);
        }

        return data_get($model, $field, $default);
    }
}

if (! function_exists('available_locales')) {
    function available_locales(): array
    {
        return collect(config('locales.supported', []))
            ->filter(fn ($config) => ($config['is_active'] ?? true))
            ->all();
    }
}

if (! function_exists('locale_switch_url')) {
    function locale_switch_url(string $targetLocale): string
    {
        $supported = config('locales.supported', []);

        if (! array_key_exists($targetLocale, $supported)) {
            return url('/');
        }

        $request = request();
        $route = $request->route();
        $query = $request->getQueryString();
        $queryString = $query ? '?'.$query : '';

        // Build target URL by resolving route parameters and using localized route
        $targetPath = function () use ($targetLocale, $route, $supported, $queryString) {
            $baseName = $route?->getName();
            
            // Remove any locale prefix from base name
            foreach (['localized.vi.', 'localized.en.', 'localized.el.', 'localized.'] as $prefix) {
                if (str_starts_with($baseName ?? '', $prefix)) {
                    $baseName = substr($baseName, strlen($prefix));
                    break;
                }
            }

            // Build target route name
            $targetName = "localized.{$targetLocale}.{$baseName}";
            
            // Check if route exists, otherwise fallback to localized home
            if (! Route::has($targetName)) {
                $prefix = $supported[$targetLocale]['prefix'] ?? $targetLocale;
                return url('/' . $prefix) . $queryString;
            }

            // Resolve slug parameters
            $resolvedValues = [];
            foreach ($route?->parameters() ?? [] as $value) {
                if (is_object($value) && method_exists($value, 'localizedSlug')) {
                    $resolvedValues[] = $value->localizedSlug($targetLocale);
                } elseif (is_object($value) && method_exists($value, 'getRouteKey')) {
                    $resolvedValues[] = $value->getRouteKey();
                } else {
                    $resolvedValues[] = $value;
                }
            }

            // Map parameters to target route
            $targetRoute = Route::getRoutes()->getByName($targetName);
            $targetParamNames = $targetRoute?->parameterNames() ?? [];

            $mapped = [];
            foreach ($targetParamNames as $i => $paramName) {
                if (isset($resolvedValues[$i])) {
                    $mapped[$paramName] = $resolvedValues[$i];
                }
            }

            try {
                return route($targetName, $mapped) . $queryString;
            } catch (\Throwable) {
                $prefix = $supported[$targetLocale]['prefix'] ?? $targetLocale;
                return url('/' . $prefix) . $queryString;
            }
        };

        // No route matched, go to localized home
        if (! $route || ! $route->getName()) {
            $prefix = $supported[$targetLocale]['prefix'] ?? $targetLocale;
            return url('/' . $prefix) . $queryString;
        }

        // Always use localized route for target locale
        return $targetPath();
    }
}

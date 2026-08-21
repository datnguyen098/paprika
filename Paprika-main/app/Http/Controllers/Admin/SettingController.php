<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\UploadService;
use App\Support\OpenDays;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(private readonly UploadService $uploads) {}

    public function edit(): View
    {
        return view('admin.settings.edit', [
            'title' => 'Cài đặt website',
            'action' => route('admin.settings.update'),
            'keys' => $this->generalKeys(),
            'translationKeyGroups' => $this->generalTranslationKeyGroups(),
            'imageKeys' => [],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $translationKeys = $this->flattenKeyGroups($this->generalTranslationKeyGroups());
        $data = $request->validate($this->rulesFor($this->generalKeys()) + $this->rulesFor($translationKeys));
        $data['show_dish_prices'] = $request->boolean('show_dish_prices') ? '1' : '0';
        $data['disable_offline_payment'] = $request->boolean('disable_offline_payment') ? '1' : '0';
        $data['open_days'] = implode(',', OpenDays::normalize($data['open_days'] ?? null));
        $this->saveSettings($data, $this->generalKeys(), 'general');
        $this->saveSettings($data, $translationKeys, 'translation');

        return back()->with('success', 'Đã cập nhật cài đặt website.');
    }

    public function identity(): View
    {
        return view('admin.settings.identity', [
            'title' => 'Logo & nhận diện',
            'action' => route('admin.identity.update'),
            'imageKeys' => $this->identityKeys(),
        ]);
    }

    public function updateIdentity(Request $request): RedirectResponse
    {
        $data = $request->validate($this->imageRulesFor($this->identityKeys()));
        $this->saveImages($data, $this->identityKeys(), 'identity');

        return back()->with('success', 'Đã cập nhật logo và nhận diện.');
    }

    public function seo(): View
    {
        return view('admin.settings.seo', [
            'title' => 'SEO tổng thể',
            'action' => route('admin.seo.update'),
            'keys' => $this->seoKeys(),
            'translationKeyGroups' => $this->seoTranslationKeyGroups(),
            'imageKeys' => ['og_image' => 'OG image mặc định'],
        ]);
    }

    public function updateSeo(Request $request): RedirectResponse
    {
        $translationKeys = $this->flattenKeyGroups($this->seoTranslationKeyGroups());
        $data = $request->validate($this->rulesFor($this->seoKeys()) + $this->rulesFor($translationKeys) + $this->imageRulesFor(['og_image' => 'OG image mặc định']));

        $this->saveSettings($data, $this->seoKeys(), 'seo');
        $this->saveSettings($data, $translationKeys, 'translation');
        $this->saveImages($data, ['og_image' => 'OG image mặc định'], 'seo');

        return back()->with('success', 'Đã cập nhật SEO tổng thể.');
    }

    private function generalKeys(): array
    {
        return [
            'site_name' => 'Tên website',
            'restaurant_name' => 'Tên quán',
            'slogan' => 'Slogan',
            'short_description' => 'Mô tả ngắn',
            'hotline' => 'Hotline mặc định',
            'phone' => 'Số điện thoại mặc định',
            'show_dish_prices' => 'Hiển thị giá món ăn ngoài website',
            'default_locale' => 'Ngôn ngữ mặc định hiển thị',
            'business_timezone' => 'Múi giờ kinh doanh',
            'open_days' => 'Ngày mở cửa',
            'facebook_url' => 'Link Facebook',
            'zalo_url' => 'Link Zalo',
            'tiktok_url' => 'Link TikTok',
            'instagram_url' => 'Link Instagram',
            'youtube_url' => 'Link YouTube',
            'copyright' => 'Copyright footer',
            'footer_description' => 'Nội dung footer giới thiệu ngắn',
            'order_notification_email' => 'Email nhận thông báo đơn hàng',
            'disable_offline_payment' => 'Tắt thanh toán tại quán',
        ];
    }

    private function identityKeys(): array
    {
        return [
            'logo_header' => 'Logo header',
            'logo_footer' => 'Logo footer',
            'favicon' => 'Favicon',
            'default_background' => 'Ảnh nền mặc định',
        ];
    }

    private function generalTranslationKeyGroups(): array
    {
        return [
            'en' => $this->generalTranslationKeys('en', 'English'),
            'el' => $this->generalTranslationKeys('el', 'Greek'),
        ];
    }

    private function generalTranslationKeys(string $locale, string $label): array
    {
        $suffix = '_'.$locale;

        return [
            'site_name'.$suffix => "Website name ({$label})",
            'restaurant_name'.$suffix => "Restaurant name ({$label})",
            'slogan'.$suffix => "Slogan ({$label})",
            'short_description'.$suffix => "Short description ({$label})",
            'copyright'.$suffix => "Footer copyright ({$label})",
            'footer_description'.$suffix => "Footer description ({$label})",
        ];
    }

    private function seoKeys(): array
    {
        return [
            'default_meta_title' => 'Meta title mặc định',
            'default_meta_description' => 'Meta description mặc định',
            'default_meta_keywords' => 'Meta keywords mặc định',
            'google_analytics_code' => 'Google Analytics code',
            'google_search_console' => 'Google Search Console verification',
            'facebook_pixel_code' => 'Facebook Pixel code',
            'robots_txt_content' => 'Robots.txt content',
            'schema_restaurant_name' => 'Schema tên quán',
            'schema_price_range' => 'Schema khoảng giá',
            'schema_latitude' => 'Schema latitude',
            'schema_longitude' => 'Schema longitude',
        ];
    }

    private function seoTranslationKeyGroups(): array
    {
        return [
            'en' => $this->seoTranslationKeys('en', 'English'),
            'el' => $this->seoTranslationKeys('el', 'Greek'),
        ];
    }

    private function seoTranslationKeys(string $locale, string $label): array
    {
        $suffix = '_'.$locale;

        return [
            'default_meta_title'.$suffix => "Default meta title ({$label})",
            'default_meta_description'.$suffix => "Default meta description ({$label})",
            'default_meta_keywords'.$suffix => "Default meta keywords ({$label})",
            'schema_restaurant_name'.$suffix => "Schema restaurant name ({$label})",
        ];
    }

    private function flattenKeyGroups(array $groups): array
    {
        return collect($groups)
            ->flatMap(fn (array $keys): array => $keys)
            ->all();
    }

    private function rulesFor(array $keys): array
    {
        return collect($keys)
            ->mapWithKeys(function ($label, $key): array {
                return match ($key) {
                    'show_dish_prices' => [$key => ['nullable', 'boolean']],
                    'default_locale' => [$key => ['nullable', 'string', 'in:vi,en,el']],
                    'business_timezone' => [$key => ['nullable', 'timezone']],
                    'open_days' => [$key => ['required', 'array', 'min:1'], $key.'.*' => ['integer', 'between:0,6']],
                    'order_notification_email' => [$key => ['nullable', 'email']],
                    'hotline', 'phone' => [$key => ['nullable', 'string', 'max:40']],
                    'disable_offline_payment' => [$key => ['nullable', 'boolean']],
                    default => [$key => ['nullable', 'string']],
                };
            })
            ->all();
    }

    private function imageRulesFor(array $keys): array
    {
        return collect($keys)
            ->mapWithKeys(fn ($label, $key): array => [$key => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:'.config('uploads.max_image_kb')]])
            ->all();
    }

    private function saveSettings(array $data, array $keys, string $group): void
    {
        foreach (array_keys($keys) as $key) {
            SiteSetting::set($key, $data[$key] ?? null, 'textarea', $group);
        }
    }

    private function saveImages(array $data, array $keys, string $group): void
    {
        foreach (array_keys($keys) as $key) {
            if (! request()->hasFile($key)) {
                continue;
            }

            $oldPath = setting($key);
            $path = $this->uploads->uploadImage(request()->file($key), 'settings', $this->imageProfileFor($key));
            SiteSetting::set($key, $path, 'image', $group);
            $this->uploads->deleteImage($oldPath);
        }
    }

    private function imageProfileFor(string $key): string
    {
        return match ($key) {
            'logo_header', 'logo_footer', 'favicon' => 'brand',
            'default_background', 'og_image' => 'hero',
            default => 'content',
        };
    }
}

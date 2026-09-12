<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;

class AboutController extends Controller
{
    /**
     * GET /api/v1/about
     *
     * Lấy dữ liệu từ bảng pages (slug = 'about') kết hợp
     * site_settings cho mission / vision / team / stats.
     */
    public function index(): JsonResponse
    {
        $page = Page::query()
            ->where('slug', 'about')
            ->where('is_active', true)
            ->first();

        if (! $page) {
            // Fallback dev: chưa seed Page slug='about' thì trả data mặc định
            // để Flutter UI không bị trắng. Khi đã seed DB, block này không chạy.
            return response()->json([
                'success' => true,
                'data'    => [
                    'id'           => null,
                    'title'        => 'About Paprika',
                    'subtitle'     => $this->fallbackSubtitle(),
                    'story'        => '<p>Paprika is a Vietnamese kitchen in Patras, serving pho, banh mi, nem, fresh rolls, grilled dishes and some familiar Greek favorites.</p><p>The restaurant focuses on fresh flavors, clear service and a convenient ordering experience for dine-in, takeaway or delivery.</p>',
                    'mission'      => $this->fallbackMission(),
                    'vision'       => $this->fallbackVision(),
                    'cover_image'  => null,
                    'team_members' => [],
                    'stats'        => [],
                ],
            ]);
        }

        $settings = $this->getAboutSettings();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'           => $page->id,
                'title'        => $page->title,
                'subtitle'     => $settings['subtitle'],
                'story'        => $page->content,
                'mission'      => $settings['mission'],
                'vision'       => $settings['vision'],
                'cover_image'  => $page->image,
                'team_members' => $settings['team_members'],
                'stats'        => $settings['stats'],
            ],
        ]);
    }

    /**
     * Đọc các setting key 'about_*' và giải mã JSON cho
     * team_members + stats. Trả về mảng rỗng nếu setting
     * chưa được cấu hình.
     *
     * @return array{subtitle:?string,mission:?string,vision:?string,team_members:array<int,array<string,mixed>>,stats:array<int,array<string,mixed>>}
     */
    private function getAboutSettings(): array
    {
        $raw = SiteSetting::query()
            ->whereIn('key', [
                'about_subtitle',
                'about_mission',
                'about_vision',
                'about_team_members',
                'about_stats',
            ])
            ->pluck('value', 'key');

        return [
            'subtitle'     => $raw['about_subtitle'] ?? $this->fallbackSubtitle(),
            'mission'      => $raw['about_mission']  ?? $this->fallbackMission(),
            'vision'       => $raw['about_vision']   ?? $this->fallbackVision(),
            'team_members' => $this->decodeJson($raw['about_team_members'] ?? null, []),
            'stats'        => $this->decodeJson($raw['about_stats']        ?? null, []),
        ];
    }

    private function fallbackSubtitle(): string
    {
        return 'Vietnamese kitchen in Patras';
    }

    private function fallbackMission(): string
    {
        return 'To bring authentic Vietnamese flavors to Patras with fresh ingredients and warm hospitality.';
    }

    private function fallbackVision(): string
    {
        return 'A friendly place where Vietnamese and Greek food lovers can enjoy fresh, honest cooking together.';
    }

    /**
     * @param  array<int,array<string,mixed>>  $fallback
     * @return array<int,array<string,mixed>>
     */
    private function decodeJson(?string $value, array $fallback): array
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : $fallback;
    }
}

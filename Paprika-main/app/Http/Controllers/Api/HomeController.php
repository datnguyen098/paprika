<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Dish;
use App\Models\GalleryImage;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * GET /api/v1/home
 *
 * Trả về dữ liệu trang chủ từ DB — không hardcode.
 * Mapping field Flutter ↔ BE xem FE `HomeModel` (lib/data/models/home_model.dart).
 *
 * Vì sao mỗi section đều có fallback `[]` / null: để FE tự xử lý
 * section rỗng (SizedBox.shrink) thay vì trả lỗi 500.
 */
class HomeController extends Controller
{
    /** Giới hạn section để payload không phình. */
    private const LIMIT_BANNERS = 5;
    private const LIMIT_FEATURED = 6;
    private const LIMIT_TESTIMONIALS = 4;
    private const LIMIT_POSTS = 3;
    private const LIMIT_PROMOTIONS = 4;
    private const LIMIT_GALLERY = 6;
    private const LIMIT_CATEGORIES = 8;

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'banners'        => $this->banners(),
                'categories'     => $this->categories(),
                'featured'       => $this->featured(),
                'testimonials'   => $this->testimonials(),
                'latest_posts'   => $this->latestPosts(),
                'promotions'     => $this->promotions(),
                'gallery_images' => $this->gallery(),
                // Popup lấy từ promotions có placement='popup' và đang active.
                // Nếu không có → null để FE không show popup.
                'promo_popup'    => $this->promoPopup(),
            ],
        ]);
    }

    /**
     * Banners active, sắp theo sort_order rồi id.
     * Trả field Flutter đang dùng: id, title, subtitle, image, cta_label, cta_link.
     */
    private function banners(): array
    {
        return Banner::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(self::LIMIT_BANNERS)
            ->get()
            ->map(fn (Banner $b): array => [
                'id'        => $b->id,
                'title'     => $b->title,
                'subtitle'  => $b->subtitle,
                'image'     => media_url($b->image),
                'cta_label' => $b->button_text,
                'cta_link'  => $b->button_link,
            ])
            ->all();
    }

    /**
     * Categories loại dish (type='dish'), active, sort theo sort_order rồi id.
     * Flutter đang map: id, name, icon.
     * DB không có cột icon → fallback 'restaurant'.
     */
    private function categories(): array
    {
        return Category::query()
            ->dish()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(self::LIMIT_CATEGORIES)
            ->get()
            ->map(fn (Category $c): array => [
                'id'   => $c->id,
                'name' => $c->name,
                // 'icon' trong DB không tồn tại → trả null để FE tự chọn icon mặc định.
                'icon' => null,
            ])
            ->all();
    }

    /**
     * Dishes featured + active. Map field Flutter:
     * id, name, image, price (decimal), old_price (sale_price),
     * rating (DB chưa có → 0), is_new (DB chưa có → false).
     */
    private function featured(): array
    {
        return Dish::query()
            ->featured()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(self::LIMIT_FEATURED)
            ->get()
            ->map(fn (Dish $d): array => [
                'id'        => $d->id,
                'name'      => $d->name,
                'image'     => media_url($d->image),
                'price'     => (float) $d->price,
                'old_price' => $d->sale_price !== null ? (float) $d->sale_price : null,
                'rating'    => 0.0,
                'is_new'    => false,
            ])
            ->all();
    }

    /**
     * Testimonials active. Map field Flutter:
     * id, name, avatar, rating, content.
     */
    private function testimonials(): array
    {
        return Testimonial::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(self::LIMIT_TESTIMONIALS)
            ->get()
            ->map(fn (Testimonial $t): array => [
                'id'      => (string) $t->id,
                'name'    => $t->name,
                'avatar'  => media_url($t->avatar),
                'rating'  => (int) $t->rating,
                'content' => $t->content,
            ])
            ->all();
    }

    /**
     * Posts published mới nhất. Map field Flutter:
     * id, title, excerpt, image, author, created_at.
     * DB Post không có cột author → trả null (FE sẽ hiển thị "Đội ngũ Paprika").
     */
    private function latestPosts(): array
    {
        return Post::query()
            ->published()
            ->orderByDesc('published_at')
            ->limit(self::LIMIT_POSTS)
            ->get()
            ->map(fn (Post $p): array => [
                'id'         => $p->id,
                'title'      => $p->title,
                'excerpt'    => $p->excerpt,
                'image'      => media_url($p->thumbnail),
                'author'     => null,
                'created_at' => optional($p->published_at)->toDateString(),
            ])
            ->all();
    }

    /**
     * Promotions placement='home' (card trên trang chủ).
     * Flutter đang dùng field: id, badge, title, subtitle, description,
     * image, cta_label, cta_link.
     */
    private function promotions(): array
    {
        return Promotion::query()
            ->current()
            ->where('placement', 'home')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(self::LIMIT_PROMOTIONS)
            ->get()
            ->map(fn (Promotion $p): array => [
                'id'          => $p->id,
                'badge'       => $p->badge,
                'title'       => $p->title,
                'subtitle'    => $p->subtitle,
                'description' => $p->description,
                'image'       => media_url($p->image),
                'cta_label'   => $p->button_text,
                'cta_link'    => $p->button_link,
            ])
            ->all();
    }

    /**
     * Gallery: active + featured trước, sort theo sort_order.
     * Flutter đang dùng: id, title, alt_text, image, branch.name.
     *
     * Lọc ảnh không tồn tại trên disk (DB có thể có row legacy khi file
     * bị xóa / chưa upload xong). Tránh trả URL 404 cho FE.
     */
    private function gallery(): array
    {
        return GalleryImage::query()
            ->active()
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(self::LIMIT_GALLERY)
            ->get()
            ->filter(fn (GalleryImage $g): bool => $this->imageExists($g->image))
            ->map(function (GalleryImage $g): array {
                $branch = $g->branch;
                return [
                    'id'       => $g->id,
                    'title'    => $g->title,
                    'alt_text' => $g->alt_text,
                    'image'    => media_url($g->image),
                    'branch'   => $branch ? ['name' => $branch->name] : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Kiểm tra file ảnh có tồn tại trên disk không.
     * Xử lý cả 2 dạng path DB lưu:
     *   - relative (vd `gallery/x.jpg`) → check storage/app/public/
     *   - absolute (vd `/paprika/x.jpg`) → check public/
     */
    private function imageExists(?string $path): bool
    {
        if (blank($path)) {
            return false;
        }
        if (str_starts_with($path, '/')) {
            return file_exists(public_path(ltrim($path, '/')));
        }
        if (str_starts_with($path, 'http')) {
            return true; // Không check URL bên ngoài, cứ pass qua.
        }
        return Storage::disk(config('uploads.disk', 'public'))->exists($path);
    }

    /**
     * Promo popup: lấy promotion placement='popup' đang current.
     * FE map field: enabled, title, content, image, cta_label, cta_link, expires_at.
     *
     * Lý do không dùng SiteSetting: DB hiện không có group 'popup',
     * đồng thời cùng một schema với promotions cho nhất quán.
     */
    private function promoPopup(): ?array
    {
        $p = Promotion::query()
            ->current()
            ->where('placement', 'popup')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if (!$p) {
            return null;
        }

        return [
            'enabled'    => true,
            'title'      => $p->title,
            'content'    => $p->subtitle ?? $p->description,
            'image'      => media_url($p->image),
            'cta_label'  => $p->button_text,
            'cta_link'   => $p->button_link,
            'expires_at' => optional($p->ends_at)?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    /**
     * GET /api/v1/home
     *
     * Mock thuần — phục vụ phát triển mobile. Sau này nối DB và các bảng
     * banners/featured_dishes/categories/testimonials/posts của Người 3/4.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'banners' => [
                    [
                        'id'        => 1,
                        'title'     => 'Hương vị Việt — Hy',
                        'subtitle'  => 'Trải nghiệm ẩm thực độc đáo mùa hè',
                        'image'     => 'banners/banner-1.jpg',
                        'cta_label' => 'Đặt bàn ngay',
                        'cta_link'  => '/reservation',
                    ],
                    [
                        'id'        => 2,
                        'title'     => 'Đêm Hy Lạp tại Paprika',
                        'subtitle'  => 'Sơ chế hải sản tươi sống mỗi tối',
                        'image'     => 'banners/banner-2.jpg',
                        'cta_label' => 'Xem menu',
                        'cta_link'  => '/menu',
                    ],
                    [
                        'id'        => 3,
                        'title'     => 'Ưu đãi thành viên mới',
                        'subtitle'  => 'Giảm 20% cho đơn đầu tiên',
                        'image'     => 'banners/banner-3.jpg',
                        'cta_label' => 'Đăng ký',
                        'cta_link'  => '/auth/register',
                    ],
                ],

                'categories' => [
                    ['id' => 1, 'name' => 'Khai vị',     'icon' => 'appetizer'],
                    ['id' => 2, 'name' => 'Món chính',   'icon' => 'main'],
                    ['id' => 3, 'name' => 'Hải sản',     'icon' => 'seafood'],
                    ['id' => 4, 'name' => 'Lẩu',         'icon' => 'hotpot'],
                    ['id' => 5, 'name' => 'Tráng miệng', 'icon' => 'dessert'],
                    ['id' => 6, 'name' => 'Đồ uống',     'icon' => 'drink'],
                ],

                'featured' => [
                    [
                        'id'        => 101,
                        'name'      => 'Phở bò Paprika',
                        'image'     => 'dishes/pho-paprika.jpg',
                        'price'     => 89000,
                        'old_price' => null,
                        'rating'    => 4.8,
                        'is_new'    => true,
                    ],
                    [
                        'id'        => 102,
                        'name'      => 'Moussaka Hy Lạp',
                        'image'     => 'dishes/moussaka.jpg',
                        'price'     => 149000,
                        'old_price' => 179000,
                        'rating'    => 4.9,
                        'is_new'    => false,
                    ],
                    [
                        'id'        => 103,
                        'name'      => 'Cá chẽm nướng giấy bạc',
                        'image'     => 'dishes/ca-chem.jpg',
                        'price'     => 219000,
                        'old_price' => null,
                        'rating'    => 4.7,
                        'is_new'    => false,
                    ],
                ],

                'testimonials' => [
                    [
                        'id'      => 'r1',
                        'name'    => 'Trần Minh',
                        'avatar'  => 'avatars/m1.jpg',
                        'rating'  => 5,
                        'content' => 'Phở ở đây có hương paprika rất lạ, nhưng vẫn giữ vị quen thuộc. Sẽ quay lại!',
                    ],
                    [
                        'id'      => 'r2',
                        'name'    => 'Sophia Lee',
                        'avatar'  => 'avatars/m2.jpg',
                        'rating'  => 5,
                        'content' => 'The Greek platter is authentic. Family-friendly vibe and great service.',
                    ],
                ],

                'latest_posts' => [
                    [
                        'id'         => 901,
                        'title'      => 'Câu chuyện mùa thu tại Patras',
                        'excerpt'    => 'Nguyên liệu Hy Lạp chính hãng theo mùa — và cách chúng tôi đưa vào mâm cơm Việt.',
                        'image'      => 'posts/post-1.jpg',
                        'author'     => 'Chef Nguyễn Văn A',
                        'created_at' => '2026-08-21',
                    ],
                    [
                        'id'         => 902,
                        'title'      => 'Paprika — gia vị kết nối hai nền văn hóa',
                        'excerpt'    => 'Hành trình từ Patras đến Đà Nẵng và bài học về cân bằng hương vị.',
                        'image'      => 'posts/post-2.jpg',
                        'author'     => 'Chef Maria Patras',
                        'created_at' => '2026-09-01',
                    ],
                ],

                'promo_popup' => [
                    'enabled'    => true,
                    'title'      => 'Ưu đãi tháng 9',
                    'content'    => 'Giảm 20% cho đơn đầu tiên khi đăng ký thành viên.',
                    'image'      => 'promo/promo-9.jpg',
                    'cta_label'  => 'Đăng ký ngay',
                    'cta_link'   => '/auth/register',
                    'expires_at' => '2026-09-30T23:59:59+07:00',
                ],
            ],
        ]);
    }
}

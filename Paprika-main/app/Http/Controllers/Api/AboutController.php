<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AboutController extends Controller
{
    /**
     * GET /api/v1/about
     *
     * Mock thuần — sau này nối DB bảng about_pages.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'id'           => 1,
                'title'        => 'Paprika Patras',
                'subtitle'     => 'Hành trình hương vị Việt — Hy',
                'story'        => 'Paprika Patras ra đời từ tình yêu ẩm thực của đầu bếp Việt với vùng đất Patras — nơi ông cha ta đã gieo mầm gia vị paprika trên đất Hy Lạp. Mỗi món ăn là một câu chuyện kể giữa hai nền văn hóa, từ mâm cơm gia đình Việt đến bàn tiệc mezze của biển Địa Trung Hải.',
                'mission'      => 'Mang đến trải nghiệm ăn uống chân thật, nơi khách hàng cảm nhận được sự giao thoa giữa hương vị quê hương và tinh hoa ẩm thực thế giới.',
                'vision'       => 'Trở thành thương hiệu nhà hàng Việt — Hy đầu tiên lan tỏa giá trị văn hóa ẩm thực từ Đông sang Tây.',
                'cover_image'  => 'about/cover.jpg',
                'team_members' => [
                    [
                        'name'   => 'Chef Nguyễn Văn A',
                        'role'   => 'Bếp trưởng',
                        'avatar' => 'team/a.jpg',
                    ],
                    [
                        'name'   => 'Chef Maria Patras',
                        'role'   => 'Cố vấn Hy Lạp',
                        'avatar' => 'team/m.jpg',
                    ],
                    [
                        'name'   => 'Lê Thị B',
                        'role'   => 'Quản lý nhà hàng',
                        'avatar' => 'team/b.jpg',
                    ],
                ],
                'stats' => [
                    ['label' => 'Chi nhánh',       'value' => 4],
                    ['label' => 'Món trong menu',  'value' => 120],
                    ['label' => 'Khách hàng/năm',  'value' => '120k+'],
                    ['label' => 'Năm kinh nghiệm', 'value' => 12],
                ],
            ],
        ]);
    }
}

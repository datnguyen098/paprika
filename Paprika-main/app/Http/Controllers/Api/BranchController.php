<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BranchController extends Controller
{
    /**
     * GET /api/v1/branches
     * GET /api/v1/branches/{id}
     *
     * Mock thuần — hard-coded 4 chi nhánh. Sau này nối DB bảng branches.
     */
    private const BRANCHES = [
        1 => [
            'id'            => 1,
            'name'          => 'Paprika Patras — Quận 1',
            'address'       => '12 Lê Lợi, Bến Nghé, Quận 1, TP.HCM',
            'phone'         => '+84 28 3822 1111',
            'email'         => 'q1@paprika-patras.vn',
            'opening_hours' => '10:00 - 22:30',
            'latitude'      => 10.7720,
            'longitude'     => 106.7009,
            'image'         => 'branches/q1.jpg',
            'description'   => 'Chi nhánh trung tâm với view sông Sài Gòn, đặc trưng ẩm thực Hy Lạp và Việt Nam.',
            'is_active'     => true,
        ],
        2 => [
            'id'            => 2,
            'name'          => 'Paprika Patras — Quận 7',
            'address'       => '15 Nguyễn Lương Bằng, Tân Phú, Quận 7, TP.HCM',
            'phone'         => '+84 28 5413 2222',
            'email'         => 'q7@paprika-patras.vn',
            'opening_hours' => '10:30 - 22:00',
            'latitude'      => 10.7340,
            'longitude'     => 106.7212,
            'image'         => 'branches/q7.jpg',
            'description'   => 'Không gian gia đình ở khu Phú Mỹ Hưng, phù hợp tiệc cuối tuần.',
            'is_active'     => true,
        ],
        3 => [
            'id'            => 3,
            'name'          => 'Paprika Patras — Hà Nội',
            'address'       => '42 Phố Tràng Tiền, Hoàn Kiếm, Hà Nội',
            'phone'         => '+84 24 3938 3333',
            'email'         => 'hn@paprika-patras.vn',
            'opening_hours' => '11:00 - 22:00',
            'latitude'      => 21.0245,
            'longitude'     => 105.8539,
            'image'         => 'branches/hn.jpg',
            'description'   => 'Chi nhánh Hà Nội gần Hồ Gươm, phục vụ món Việt mùa thu và cá Hy Lạp nhập khẩu.',
            'is_active'     => true,
        ],
        4 => [
            'id'            => 4,
            'name'          => 'Paprika Patras — Đà Nẵng',
            'address'       => 'Bãi Biển Mỹ Khê, Sơn Trà, Đà Nẵng',
            'phone'         => '+84 236 3888 444',
            'email'         => 'dn@paprika-patras.vn',
            'opening_hours' => '10:00 - 23:00',
            'latitude'      => 16.0610,
            'longitude'     => 108.2450,
            'image'         => 'branches/dn.jpg',
            'description'   => 'Chi nhánh view biển, đặc trưng các món nướng và hải sản Đà Nẵng kết hợp Hy Lạp.',
            'is_active'     => true,
        ],
    ];

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => array_values(self::BRANCHES),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $branch = self::BRANCHES[$id] ?? null;

        if (! $branch) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy chi nhánh với id = {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $branch,
        ]);
    }
}

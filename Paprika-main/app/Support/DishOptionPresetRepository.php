<?php

namespace App\Support;

use App\Models\DishOptionGroup;
use Illuminate\Support\Collection;

class DishOptionPresetRepository
{
    public function all(): Collection
    {
        $stored = json_decode((string) setting('dish_option_presets', ''), true);

        return collect(is_array($stored) && $stored !== [] ? $stored : $this->defaults());
    }

    public function defaults(): array
    {
        return [
            [
                'name' => 'Đồ ăn',
                'slug' => 'food',
                'description' => 'Bộ lựa chọn phổ biến cho món Việt Nam và món Hy Lạp.',
                'sort_order' => 0,
                'groups' => [
                    [
                        'name' => 'Kích cỡ',
                        'type' => DishOptionGroup::TYPE_SINGLE,
                        'description' => 'Regular phù hợp một người, Large nhiều topping hơn.',
                        'is_required' => true,
                        'is_active' => true,
                        'min_select' => 0,
                        'max_select' => 1,
                        'sort_order' => 0,
                        'options' => [
                            ['name' => 'Regular', 'price_delta' => '0.00', 'is_default' => true, 'is_active' => true, 'sort_order' => 0],
                            ['name' => 'Large', 'price_delta' => '2.00', 'is_default' => false, 'is_active' => true, 'sort_order' => 1],
                        ],
                    ],
                    [
                        'name' => 'Độ cay',
                        'type' => DishOptionGroup::TYPE_SINGLE,
                        'description' => 'Điều chỉnh độ cay theo khẩu vị.',
                        'is_required' => true,
                        'is_active' => true,
                        'min_select' => 0,
                        'max_select' => 1,
                        'sort_order' => 1,
                        'options' => [
                            ['name' => 'Không cay', 'price_delta' => '0.00', 'is_default' => false, 'is_active' => true, 'sort_order' => 0],
                            ['name' => 'Cay vừa', 'price_delta' => '0.00', 'is_default' => true, 'is_active' => true, 'sort_order' => 1],
                            ['name' => 'Cay nhiều', 'price_delta' => '0.00', 'is_default' => false, 'is_active' => true, 'sort_order' => 2],
                        ],
                    ],
                    [
                        'name' => 'Topping thêm',
                        'type' => DishOptionGroup::TYPE_MULTIPLE,
                        'description' => 'Cho phép khách thêm phần ăn hoặc sốt.',
                        'is_required' => false,
                        'is_active' => true,
                        'min_select' => 0,
                        'max_select' => 3,
                        'sort_order' => 2,
                        'options' => [
                            ['name' => 'Thêm thịt / nhân', 'price_delta' => '2.00', 'is_default' => false, 'is_active' => true, 'sort_order' => 0],
                            ['name' => 'Thêm rau thơm', 'price_delta' => '0.50', 'is_default' => false, 'is_active' => true, 'sort_order' => 1],
                            ['name' => 'Thêm sốt riêng', 'price_delta' => '0.50', 'is_default' => false, 'is_active' => true, 'sort_order' => 2],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Đồ uống',
                'slug' => 'drinks',
                'description' => 'Bộ lựa chọn dung tích và lượng đá cho đồ uống.',
                'sort_order' => 1,
                'groups' => [
                    [
                        'name' => 'Kích cỡ',
                        'type' => DishOptionGroup::TYPE_SINGLE,
                        'description' => 'Chọn dung tích đồ uống.',
                        'is_required' => true,
                        'is_active' => true,
                        'min_select' => 0,
                        'max_select' => 1,
                        'sort_order' => 0,
                        'options' => [
                            ['name' => 'Regular', 'price_delta' => '0.00', 'is_default' => true, 'is_active' => true, 'sort_order' => 0],
                            ['name' => 'Large', 'price_delta' => '0.80', 'is_default' => false, 'is_active' => true, 'sort_order' => 1],
                        ],
                    ],
                    [
                        'name' => 'Đá',
                        'type' => DishOptionGroup::TYPE_SINGLE,
                        'description' => 'Tùy chỉnh lượng đá.',
                        'is_required' => true,
                        'is_active' => true,
                        'min_select' => 0,
                        'max_select' => 1,
                        'sort_order' => 1,
                        'options' => [
                            ['name' => 'Đá bình thường', 'price_delta' => '0.00', 'is_default' => true, 'is_active' => true, 'sort_order' => 0],
                            ['name' => 'Ít đá', 'price_delta' => '0.00', 'is_default' => false, 'is_active' => true, 'sort_order' => 1],
                            ['name' => 'Không đá', 'price_delta' => '0.00', 'is_default' => false, 'is_active' => true, 'sort_order' => 2],
                        ],
                    ],
                ],
            ],
        ];
    }
}

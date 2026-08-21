<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(config('permissions.permissions'))
            ->mapWithKeys(function (array $data, string $slug): array {
                return [$slug => Permission::updateOrCreate(
                    ['slug' => $slug],
                    ['name' => $data[0], 'group' => $data[1]]
                )];
            });

        $roles = [
            'super-admin' => [
                'name' => 'Super Admin',
                'description' => 'Toàn quyền quản trị website.',
                'permissions' => $permissions->keys()->all(),
                'is_system' => true,
            ],
            'content-manager' => [
                'name' => 'Quản lý nội dung',
                'description' => 'Quản lý món ăn, bài viết, trang tĩnh, banner, ưu đãi và không gian quán.',
                'permissions' => $permissions->keys()->filter(fn ($key) => str_starts_with($key, 'dishes.')
                    || str_starts_with($key, 'categories.')
                    || str_starts_with($key, 'posts.')
                    || str_starts_with($key, 'pages.')
                    || str_starts_with($key, 'menus.')
                    || str_starts_with($key, 'gallery.')
                    || str_starts_with($key, 'orders.')
                    || str_starts_with($key, 'vouchers.')
                    || str_starts_with($key, 'restaurant_tables.')
                    || str_starts_with($key, 'reservations.')
                    || str_starts_with($key, 'banners.')
                    || str_starts_with($key, 'promotions.')
                    || str_starts_with($key, 'testimonials.')
                    || $key === 'dashboard.view')->values()->all(),
                'is_system' => true,
            ],
            'receptionist' => [
                'name' => 'Lễ tân',
                'description' => 'Xử lý đặt bàn, liên hệ và chat online.',
                'permissions' => [
                    'dashboard.view',
                    'orders.view', 'orders.update',
                    'restaurant_tables.view', 'restaurant_tables.create', 'restaurant_tables.update', 'restaurant_tables.delete',
                    'reservations.view', 'reservations.update',
                    'contacts.view', 'contacts.update',
                    'chats.view', 'chats.reply', 'chats.update',
                ],
                'is_system' => true,
            ],
            'marketing' => [
                'name' => 'Marketing',
                'description' => 'Quản lý banner, ưu đãi, bài viết, SEO và dịch thuật.',
                'permissions' => $permissions->keys()->filter(fn ($key) => str_starts_with($key, 'banners.')
                    || str_starts_with($key, 'promotions.')
                    || str_starts_with($key, 'vouchers.')
                    || str_starts_with($key, 'posts.')
                    || str_starts_with($key, 'seo.')
                    || str_starts_with($key, 'translations.')
                    || str_starts_with($key, 'gallery.')
                    || $key === 'dashboard.view')->values()->all(),
                'is_system' => true,
            ],
            'viewer' => [
                'name' => 'Viewer',
                'description' => 'Chỉ xem dữ liệu, không sửa/xóa.',
                'permissions' => $permissions->keys()->filter(fn ($key) => str_ends_with($key, '.view') || $key === 'dashboard.view')->values()->all(),
                'is_system' => true,
            ],
            'kitchen' => [
                'name' => 'Bếp',
                'description' => 'Nhận và cập nhật đơn hàng từ bếp.',
                'permissions' => [
                    'dashboard.view',
                    'kitchen.view',
                    'orders.view',
                ],
                'is_system' => true,
            ],
        ];

        foreach ($roles as $slug => $data) {
            $role = Role::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_system' => $data['is_system'],
                ]
            );

            $role->permissions()->sync($permissions->only($data['permissions'])->pluck('id')->all());
        }
    }
}

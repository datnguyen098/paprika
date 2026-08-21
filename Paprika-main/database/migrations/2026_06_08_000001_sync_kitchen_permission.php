<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $now = now();

        if (DB::table('permissions')->where('slug', 'kitchen.view')->exists()) {
            DB::table('permissions')->where('slug', 'kitchen.view')->update([
                'name' => 'Xem bếp',
                'group' => 'commerce',
                'updated_at' => $now,
            ]);
        } else {
            DB::table('permissions')->insert([
                'name' => 'Xem bếp',
                'slug' => 'kitchen.view',
                'group' => 'commerce',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (DB::table('roles')->where('slug', 'kitchen')->exists()) {
            DB::table('roles')->where('slug', 'kitchen')->update([
                'name' => 'Bếp',
                'description' => 'Nhận và cập nhật đơn hàng từ bếp.',
                'is_system' => true,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('roles')->insert([
                'name' => 'Bếp',
                'slug' => 'kitchen',
                'description' => 'Nhận và cập nhật đơn hàng từ bếp.',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', ['dashboard.view', 'kitchen.view', 'orders.view'])
            ->pluck('id', 'slug');

        $this->attachPermissions('super-admin', DB::table('permissions')->pluck('id')->all());
        $this->attachPermissions('viewer', [$permissionIds['kitchen.view'] ?? null]);
        $this->attachPermissions('kitchen', $permissionIds->only(['dashboard.view', 'kitchen.view', 'orders.view'])->values()->all());
    }

    public function down(): void
    {
        if (! Schema::hasTable('permission_role')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('slug', 'kitchen.view')->value('id');

        if ($permissionId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
        }
    }

    private function attachPermissions(string $roleSlug, array $permissionIds): void
    {
        $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');

        if (! $roleId) {
            return;
        }

        foreach (array_filter($permissionIds) as $permissionId) {
            DB::table('permission_role')->updateOrInsert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }

};

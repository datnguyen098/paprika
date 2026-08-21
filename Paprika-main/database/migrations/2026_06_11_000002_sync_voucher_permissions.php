<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = [
        'vouchers.view' => 'Xem voucher',
        'vouchers.create' => 'Thêm voucher',
        'vouchers.update' => 'Sửa voucher',
        'vouchers.delete' => 'Xóa voucher',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $now = now();

        foreach (self::PERMISSIONS as $slug => $name) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'group' => 'commerce',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $voucherPermissionIds = DB::table('permissions')
            ->whereIn('slug', array_keys(self::PERMISSIONS))
            ->pluck('id')
            ->all();

        $this->attachPermissions('super-admin', $voucherPermissionIds);
        $this->attachPermissions('content-manager', $voucherPermissionIds);
        $this->attachPermissions('marketing', $voucherPermissionIds);
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', array_keys(self::PERMISSIONS))
            ->pluck('id')
            ->all();

        if ($permissionIds !== []) {
            DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
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

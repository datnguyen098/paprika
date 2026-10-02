<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Support\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index(Request $request, VoucherService $vouchers): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        $items = $vouchers
            ->publicVouchers(isset($data['branch_id']) ? (int) $data['branch_id'] : null)
            ->map(fn (Voucher $voucher): array => $this->transformVoucher($voucher))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    private function transformVoucher(Voucher $voucher): array
    {
        return [
            'id' => $voucher->id,
            'code' => $voucher->code,
            'name' => localized_field($voucher, 'name', $voucher->name),
            'description' => localized_field($voucher, 'description', $voucher->description),
            'discount_type' => $voucher->discount_type,
            'value_label' => $voucher->displayValue(),
            'min_order_amount' => $voucher->min_order_amount,
            'max_discount_amount' => $voucher->max_discount_amount,
            'starts_at' => $voucher->starts_at?->toIso8601String(),
            'ends_at' => $voucher->ends_at?->toIso8601String(),
            'usage_limit_total' => $voucher->usage_limit_total,
            'usage_limit_per_customer' => $voucher->usage_limit_per_customer,
            'used_count' => $voucher->used_count,
            'is_default' => $voucher->is_default,
            'branch' => $voucher->branch ? [
                'id' => $voucher->branch->id,
                'name' => localized_field($voucher->branch, 'name', $voucher->branch->name),
            ] : null,
        ];
    }
}

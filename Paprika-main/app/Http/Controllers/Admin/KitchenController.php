<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Support\BranchAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class KitchenController extends Controller
{
    private const WORKFLOW_ACTIONS = ['preparing', 'ready', 'shipping'];

    public function index(Request $request): View
    {
        $orders = BranchAccess::apply(
            Order::query()
                ->with(['branch', 'items.dish'])
                ->where('payment_status', 'paid')
                ->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready'])
                ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'confirmed' THEN 2 WHEN 'preparing' THEN 3 WHEN 'ready' THEN 4 ELSE 5 END")
                ->orderBy('created_at', 'desc'),
            $request->user()
        )->get();

        return view('admin.kitchen.index', [
            'title' => 'Bếp',
            'orders' => $orders,
        ]);
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        BranchAccess::authorize($request->user(), $order->branch_id);

        if ($order->payment_status !== 'paid') {
            return response()->json([
                'ok' => false,
                'message' => 'Đơn chưa thanh toán nên chưa được chuyển vào bếp.',
            ], 422);
        }

        $validated = $request->validate([
            'action' => ['required', 'string', Rule::in(self::WORKFLOW_ACTIONS)],
        ]);

        $action = $validated['action'];

        if ($order->status === $action) {
            return response()->json(['ok' => true]);
        }

        $order->update([
            'status' => $action,
            "{$action}_at" => now(),
        ]);

        OrderActivity::create([
            'order_id' => $order->id,
            'action' => $action,
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['ok' => true, 'status' => $action]);
    }
}

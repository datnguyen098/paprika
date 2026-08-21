<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\VivaGateway;
use App\Support\PendingVivaPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    public function index()
    {
        return view('storefront.orders.lookup');
    }

    public function lookup(Request $request)
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:190'],
        ]);

        $queryRaw = trim((string) $validated['query']);
        $queryLower = mb_strtolower($queryRaw);
        $digits = preg_replace('/\D+/', '', $queryRaw);

        $orders = Order::query()
            ->when($queryLower !== '', function ($q) use ($queryLower, $digits) {
                $q->where(function ($sub) use ($queryLower, $digits) {
                    $sub->when(filter_var($queryLower, FILTER_VALIDATE_EMAIL), function ($sub2) use ($queryLower) {
                        $sub2->whereRaw('LOWER(customer_email) = ?', [$queryLower]);
                    });

                    if ($digits) {
                        $sub->orWhere('customer_phone', 'like', '%' . $digits . '%');
                    }
                });
            })
            ->latest()
            ->take(20)
            ->get(['id', 'branch_id', 'code', 'status', 'payment_method', 'payment_status', 'total', 'created_at', 'fulfillment_method'])
            ->load(['branch', 'payments']);

        return view('storefront.orders.lookup', [
            'query' => $queryRaw,
            'orders' => $orders,
        ]);
    }

    public function show(Order $order)
    {
        $order->loadMissing(['items.dish', 'branch', 'payments']);

        return view('storefront.orders.track', [
            'order' => $order,
        ]);
    }

    public function retryPayment(Order $order, VivaGateway $viva, PendingVivaPayment $pendingViva): RedirectResponse
    {
        return $pendingViva->continuePayment($order, $viva);
    }
}

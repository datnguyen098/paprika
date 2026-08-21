<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Support\BranchAccess;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        // 1. Parse period + date range
        $period = $request->query('period', '30days');
        $dateRange = $this->resolveDateRange($request, $period);
        $between = [$dateRange['start']->copy()->startOfDay(), $dateRange['end']->copy()->endOfDay()];
        $daysDiff = $dateRange['start']->diffInDays($dateRange['end']);

        // 2. Revenue from Payment model
        $revenuePaid = $this->paidPaymentRevenue($between, $user);
        $revenueRefunded = $this->refundedPaymentAmount($between, $user);
        $paidOrderCount = $this->paidPaymentQuery($user)
            ->whereBetween('paid_at', $between)
            ->distinct('order_id')
            ->count('order_id');
        $orderCount = BranchAccess::apply(Order::query(), $user)
            ->whereBetween('created_at', $between)
            ->count();
        $avgOrderValue = $paidOrderCount > 0 ? intdiv($revenuePaid, $paidOrderCount) : 0;
        $completedOrderCount = BranchAccess::apply(Order::query(), $user)
            ->where('status', 'completed')
            ->whereBetween('completed_at', $between)
            ->count();
        $activeOrderCount = BranchAccess::apply(Order::query(), $user)
            ->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready', 'shipping'])
            ->count();
        $allTimeOrderCount = BranchAccess::apply(Order::query(), $user)->count();

        // 3. Payment stats
        $paymentStats = $this->paymentStats($between, $user);

        // 4. Chart data
        $useMonthly = $daysDiff > 90;
        $revenueChart = $this->revenueChartData($between, $user, $useMonthly);
        $orderChart = $this->orderChartData($between, $user, $useMonthly);
        $statusChart = $this->statusChartData($between, $user);
        $paymentMethodChart = $this->paymentMethodChartData($between, $user);

        // 5. Tables
        $topDishes = $this->topDishes($between, $user);
        $recentOrders = BranchAccess::apply(Order::with(['branch', 'items', 'payments']), $user)
            ->whereBetween('created_at', $between)
            ->latest()
            ->limit(10)
            ->get();

        // 6. Alerts (global, no date filter)
        $pendingReservationCount = BranchAccess::apply(Reservation::query(), $user)->where('status', 'pending')->count();
        $newContactCount = BranchAccess::apply(Contact::query(), $user)->where('status', 'new')->count();

        return view('admin.dashboard', [
            'period' => $period,
            'dateRange' => $dateRange,
            'revenuePaid' => $revenuePaid,
            'revenueRefunded' => $revenueRefunded,
            'paidOrderCount' => $paidOrderCount,
            'orderCount' => $orderCount,
            'avgOrderValue' => $avgOrderValue,
            'completedOrderCount' => $completedOrderCount,
            'activeOrderCount' => $activeOrderCount,
            'allTimeOrderCount' => $allTimeOrderCount,
            'paymentStats' => $paymentStats,
            'revenueChart' => $revenueChart,
            'orderChart' => $orderChart,
            'statusChart' => $statusChart,
            'paymentMethodChart' => $paymentMethodChart,
            'topDishes' => $topDishes,
            'recentOrders' => $recentOrders,
            'pendingReservationCount' => $pendingReservationCount,
            'newContactCount' => $newContactCount,
        ]);
    }

    // ─── Period resolver ───────────────────────────────────────

    private function resolveDateRange(Request $request, string $period): array
    {
        $now = now();

        $presets = [
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            '7days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '30days' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'this_month' => [$now->copy()->startOfMonth()->startOfDay(), $now->copy()->endOfDay()],
        ];

        $labels = [
            'today' => 'Hôm nay',
            '7days' => '7 ngày',
            '30days' => '30 ngày',
            'this_month' => 'Tháng này',
            'custom' => 'Tùy chọn',
        ];

        if (array_key_exists($period, $presets)) {
            return [
                'start' => $presets[$period][0],
                'end' => $presets[$period][1],
                'period' => $period,
                'label' => $labels[$period],
            ];
        }

        // Custom range: reuse existing dateRange logic
        [$start, $end] = $this->dateRange($request);

        // Cap at 365 days
        if ($start->diffInDays($end) > 365) {
            $start = $end->copy()->subDays(365);
        }

        return [
            'start' => $start,
            'end' => $end,
            'period' => $period === 'custom' ? 'custom' : '30days',
            'label' => $period === 'custom' ? 'Tùy chọn' : ($labels[$period] ?? '30 ngày'),
        ];
    }

    private function dateRange(Request $request): array
    {
        $startDate = $request->date('start_date')
            ? Carbon::parse($request->date('start_date'))
            : now()->subDays(29);

        $endDate = $request->date('end_date')
            ? Carbon::parse($request->date('end_date'))
            : now();

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }

    // ─── Payment queries (branch-scoped via whereHas order) ────

    private function paidPaymentQuery(?User $user): \Illuminate\Database\Eloquent\Builder
    {
        return Payment::query()
            ->where('status', 'paid')
            ->whereHas('order', fn ($q) => BranchAccess::apply($q, $user));
    }

    private function paidPaymentRevenue(array $between, ?User $user): int
    {
        return (int) $this->paidPaymentQuery($user)
            ->whereBetween('paid_at', $between)
            ->sum('amount');
    }

    private function refundedPaymentAmount(array $between, ?User $user): int
    {
        return (int) Payment::query()
            ->where('status', 'refunded')
            ->whereHas('order', fn ($q) => BranchAccess::apply($q, $user))
            ->whereBetween('refunded_at', $between)
            ->sum('amount');
    }

    // ─── Payment stats ─────────────────────────────────────────

    private function paymentStats(array $between, ?User $user): array
    {
        $paidQuery = $this->paidPaymentQuery($user)
            ->whereBetween('paid_at', $between);

        $successCount = (clone $paidQuery)->count();
        $failedCount = Payment::query()
            ->where('status', 'failed')
            ->whereHas('order', fn ($q) => BranchAccess::apply($q, $user))
            ->whereBetween('created_at', $between)
            ->count();
        $onlineTotal = (int) (clone $paidQuery)->where('method', '!=', 'offline')->sum('amount');
        $offlineTotal = (int) (clone $paidQuery)->where('method', 'offline')->sum('amount');

        return [
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'online_total' => $onlineTotal,
            'offline_total' => $offlineTotal,
        ];
    }

    // ─── Chart data ────────────────────────────────────────────

    private function revenueChartData(array $between, ?User $user, bool $monthly): array
    {
        $dateExpr = $monthly
            ? "DATE_FORMAT(paid_at, '%Y-%m')"
            : 'DATE(paid_at)';

        $format = $monthly ? 'm/Y' : 'd/m';

        [$start, $end] = $between;

        $daily = $this->paidPaymentQuery($user)
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw("{$dateExpr} as date_key, COALESCE(SUM(amount), 0) as total")
            ->groupBy('date_key')
            ->orderBy('date_key')
            ->pluck('total', 'date_key')
            ->toArray();

        $labels = [];
        $data = [];

        foreach ($daily as $key => $amount) {
            $labels[] = $key;
            $data[] = round($amount / 100, 2);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function orderChartData(array $between, ?User $user, bool $monthly): array
    {
        $dateExpr = $monthly
            ? "DATE_FORMAT(created_at, '%Y-%m')"
            : 'DATE(created_at)';

        [$start, $end] = $between;

        $daily = BranchAccess::apply(Order::query(), $user)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("{$dateExpr} as date_key, COUNT(*) as total")
            ->groupBy('date_key')
            ->orderBy('date_key')
            ->pluck('total', 'date_key')
            ->toArray();

        $labels = [];
        $data = [];

        foreach ($daily as $key => $count) {
            $labels[] = $key;
            $data[] = (int) $count;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function statusChartData(array $between, ?User $user): array
    {
        $counts = BranchAccess::apply(Order::query(), $user)
            ->whereBetween('created_at', $between)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statusColors = [
            'pending' => '#d97706',
            'confirmed' => '#0284c7',
            'preparing' => '#7c3aed',
            'ready' => '#f59e0b',
            'shipping' => '#6366f1',
            'completed' => '#059669',
            'cancelled' => '#dc2626',
        ];

        $labels = [];
        $data = [];
        $colors = [];

        foreach (Order::STATUSES as $status) {
            $labels[] = Order::statusLabelFor($status, 'vi');
            $data[] = $counts[$status] ?? 0;
            $colors[] = $statusColors[$status] ?? '#64748b';
        }

        return ['labels' => $labels, 'data' => $data, 'colors' => $colors];
    }

    private function paymentMethodChartData(array $between, ?User $user): array
    {
        $methodCounts = $this->paidPaymentQuery($user)
            ->whereBetween('paid_at', $between)
            ->selectRaw('method, COUNT(*) as count')
            ->groupBy('method')
            ->pluck('count', 'method')
            ->toArray();

        $methodColors = [
            'viva' => '#7c3aed',
            'offline' => '#64748b',
            'vnpay' => '#2563eb',
            'momo' => '#dc2626',
        ];

        $labels = [];
        $data = [];
        $colors = [];

        foreach (array_keys(Payment::METHOD_LABELS) as $method) {
            $count = $methodCounts[$method] ?? 0;
            if ($count > 0) {
                $labels[] = Payment::METHOD_LABELS[$method];
                $data[] = $count;
                $colors[] = $methodColors[$method] ?? '#64748b';
            }
        }

        return ['labels' => $labels, 'data' => $data, 'colors' => $colors];
    }

    // ─── Tables ────────────────────────────────────────────────

    private function topDishes(array $between, ?User $user): \Illuminate\Support\Collection
    {
        return OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('dishes', 'order_items.dish_id', '=', 'dishes.id')
            ->leftJoin('categories', 'dishes.category_id', '=', 'categories.id')
            ->when($user->branch_id, fn ($q) => $q->where('orders.branch_id', $user->branch_id))
            ->whereHas('order.payments', fn ($q) => $q->where('status', 'paid')->whereBetween('paid_at', $between))
            ->selectRaw('order_items.dish_name, categories.name as category_name, SUM(order_items.quantity) as total_quantity, COALESCE(SUM(order_items.line_total), 0) as total_revenue')
            ->groupBy('order_items.dish_id', 'order_items.dish_name', 'categories.name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();
    }
}

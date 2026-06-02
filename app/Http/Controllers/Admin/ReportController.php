<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        $since = Carbon::now()->subDays(30)->startOfDay();

        // Daily paid revenue for the last 30 days.
        $daily = Order::query()
            ->where('payment_status', 'paid')
            ->where('paid_at', '>=', $since)
            ->selectRaw('DATE(paid_at) as day, COUNT(*) as orders, SUM(total_cents) as revenue_cents')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($r) => [
                'day' => $r->day,
                'orders' => (int) $r->orders,
                'revenue_cents' => (int) $r->revenue_cents,
            ]);

        // Top products by quantity sold (paid orders).
        $topProducts = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->where('payment_status', 'paid'))
            ->selectRaw('product_name, SUM(quantity) as qty, SUM(total_cents) as revenue_cents')
            ->groupBy('product_name')
            ->orderByDesc('qty')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'product_name' => $r->product_name,
                'qty' => (int) $r->qty,
                'revenue_cents' => (int) $r->revenue_cents,
            ]);

        $paidOrders = Order::query()->where('payment_status', 'paid');

        $summary = [
            'revenue_30d_cents' => (int) (clone $paidOrders)->where('paid_at', '>=', $since)->sum('total_cents'),
            'orders_30d' => (clone $paidOrders)->where('paid_at', '>=', $since)->count(),
            'avg_ticket_cents' => (int) round((clone $paidOrders)->avg('total_cents') ?? 0),
            'total_revenue_cents' => (int) (clone $paidOrders)->sum('total_cents'),
        ];

        // Coupon performance.
        $coupons = DB::table('orders')
            ->join('coupons', 'orders.coupon_id', '=', 'coupons.id')
            ->where('orders.payment_status', 'paid')
            ->selectRaw('coupons.code, COUNT(*) as uses, SUM(orders.discount_cents) as discount_cents')
            ->groupBy('coupons.code')
            ->orderByDesc('uses')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'code' => $r->code,
                'uses' => (int) $r->uses,
                'discount_cents' => (int) $r->discount_cents,
            ]);

        return Inertia::render('Admin/Reports/Index', [
            'summary' => $summary,
            'daily' => $daily,
            'topProducts' => $topProducts,
            'coupons' => $coupons,
        ]);
    }
}

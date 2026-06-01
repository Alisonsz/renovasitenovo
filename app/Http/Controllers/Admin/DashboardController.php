<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'products' => Product::query()->count(),
                'active_products' => Product::query()->where('is_active', true)->count(),
                'categories' => ProductCategory::query()->count(),
                'orders' => Order::query()->count(),
                'pending_orders' => Order::query()->where('payment_status', 'pending')->count(),
                'paid_orders' => Order::query()->where('payment_status', 'paid')->count(),
                'revenue_cents' => Order::query()->where('payment_status', 'paid')->sum('total_cents'),
            ],
        ]);
    }
}

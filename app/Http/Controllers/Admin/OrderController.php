<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Orders/Index', [
            'orders' => Order::query()
                ->with(['customer', 'items'])
                ->latest()
                ->paginate(15)
                ->through(fn (Order $order) => [
                    'id' => $order->id,
                    'number' => $order->number,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'payment_method' => $order->payment_method,
                    'total_cents' => $order->total_cents,
                    'created_at' => $order->created_at?->format('d/m/Y H:i'),
                    'customer' => [
                        'name' => $order->customer?->name,
                        'email' => $order->customer?->email,
                        'phone' => $order->customer?->phone,
                    ],
                    'items_count' => $order->items->sum('quantity'),
                    'pay_url' => $order->pagbank_pay_url,
                ]),
        ]);
    }
}

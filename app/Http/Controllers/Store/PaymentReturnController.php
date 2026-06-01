<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Inertia\Inertia;
use Inertia\Response;

class PaymentReturnController extends Controller
{
    public function show(Order $order): Response
    {
        $order->load(['items', 'customer']);

        return Inertia::render('Store/PaymentReturn', [
            'order' => [
                'number' => $order->number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'total_cents' => $order->total_cents,
                'pagbank_pay_url' => $order->pagbank_pay_url,
                'customer' => [
                    'name' => $order->customer->name,
                    'email' => $order->customer->email,
                ],
                'items' => $order->items->map(fn ($item) => [
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'total_cents' => $item->total_cents,
                ])->values(),
            ],
        ]);
    }
}

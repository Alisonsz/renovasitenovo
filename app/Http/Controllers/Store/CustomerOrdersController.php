<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerOrdersController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = collect();
        $searched = $request->filled(['email', 'document']);

        if ($searched) {
            $validated = $request->validate([
                'email' => ['required', 'email'],
                'document' => ['required', 'string', 'min:5'],
            ]);

            $document = preg_replace('/\D+/', '', $validated['document']);

            $orders = Order::query()
                ->with(['items', 'customer'])
                ->whereHas('customer', function ($query) use ($validated, $document) {
                    $query->where('email', $validated['email']);
                })
                ->latest()
                ->get()
                ->filter(fn (Order $order) => preg_replace('/\D+/', '', (string) $order->customer?->document) === $document)
                ->map(fn (Order $order) => [
                    'number' => $order->number,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'payment_method' => $order->payment_method,
                    'total_cents' => $order->total_cents,
                    'currency' => $order->currency,
                    'created_at' => $order->created_at?->toDateString(),
                    'paid_at' => $order->paid_at?->toDateString(),
                    'pay_url' => $order->pagbank_pay_url,
                    'items' => $order->items->map(fn ($item) => [
                        'name' => $item->product_name,
                        'slug' => $item->product_slug,
                        'quantity' => $item->quantity,
                        'total_cents' => $item->total_cents,
                        'image_url' => $item->metadata['image_url'] ?? null,
                    ])->values(),
                ]);
        }

        return Inertia::render('Store/CustomerOrders', [
            'filters' => [
                'email' => $request->query('email', ''),
                'document' => $request->query('document', ''),
            ],
            'searched' => $searched,
            'orders' => $orders,
        ]);
    }
}

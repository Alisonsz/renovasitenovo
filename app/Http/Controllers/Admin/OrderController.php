<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\PagBankClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public const ORDER_STATUSES = ['pending', 'processing', 'shipped', 'completed', 'cancelled', 'refunded'];

    public const PAYMENT_STATUSES = ['pending', 'authorized', 'in_analysis', 'paid', 'declined', 'failed', 'cancelled', 'refunded', 'review'];

    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'payment_status', 'search']);

        $orders = Order::query()
            ->with(['customer', 'items'])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['payment_status'] ?? null, fn ($q, $s) => $q->where('payment_status', $s))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString()
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
                ],
                'items_count' => $order->items->sum('quantity'),
            ]);

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => $filters,
            'statusOptions' => self::ORDER_STATUSES,
            'paymentStatusOptions' => self::PAYMENT_STATUSES,
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load(['customer', 'items', 'coupon', 'paymentTransactions' => fn ($q) => $q->latest()]);

        return Inertia::render('Admin/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'number' => $order->number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'subtotal_cents' => $order->subtotal_cents,
                'discount_cents' => $order->discount_cents,
                'pix_discount_cents' => $order->pix_discount_cents,
                'total_cents' => $order->total_cents,
                'created_at' => $order->created_at?->format('d/m/Y H:i'),
                'paid_at' => $order->paid_at?->format('d/m/Y H:i'),
                'cancelled_at' => $order->cancelled_at?->format('d/m/Y H:i'),
                'pagbank_order_id' => $order->pagbank_order_id,
                'pagbank_pay_url' => $order->pagbank_pay_url,
                'customer' => [
                    'id' => $order->customer?->id,
                    'name' => $order->customer?->name,
                    'email' => $order->customer?->email,
                    'phone' => $order->customer?->phone,
                    'document' => $order->customer?->document,
                ],
                'coupon' => $order->coupon ? ['code' => $order->coupon->code] : null,
                'items' => $order->items->map(fn ($item) => [
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $item->unit_price_cents,
                    'total_cents' => $item->total_cents,
                ])->values(),
                'transactions' => $order->paymentTransactions->map(fn ($t) => [
                    'id' => $t->id,
                    'provider_transaction_id' => $t->provider_transaction_id,
                    'method' => $t->method,
                    'status' => $t->status,
                    'amount_cents' => $t->amount_cents,
                    'created_at' => $t->created_at?->format('d/m/Y H:i'),
                ])->values(),
            ],
            'statusOptions' => self::ORDER_STATUSES,
            'paymentStatusOptions' => self::PAYMENT_STATUSES,
            'canRefund' => $order->payment_status === 'paid' && $order->pagbank_order_id !== null,
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(self::ORDER_STATUSES)],
            'payment_status' => ['required', Rule::in(self::PAYMENT_STATUSES)],
        ]);

        $order->forceFill([
            'status' => $data['status'],
            'payment_status' => $data['payment_status'],
            'paid_at' => $data['payment_status'] === 'paid' ? ($order->paid_at ?? now()) : $order->paid_at,
            'cancelled_at' => $data['status'] === 'cancelled' ? ($order->cancelled_at ?? now()) : $order->cancelled_at,
        ])->save();

        return back()->with('success', 'Status do pedido atualizado.');
    }

    public function refund(Request $request, Order $order, PagBankClient $client): RedirectResponse
    {
        if ($order->payment_status !== 'paid') {
            return back()->withErrors(['refund' => 'Apenas pedidos pagos podem ser estornados.']);
        }

        $charge = $order->paymentTransactions()
            ->whereNotNull('provider_transaction_id')
            ->where('status', 'PAID')
            ->latest()
            ->first();

        $chargeId = $charge?->provider_transaction_id;

        if (! $chargeId) {
            return back()->withErrors(['refund' => 'Transação do PagBank não encontrada para estorno.']);
        }

        try {
            if ($client->configured()) {
                $response = $client->refundCharge($chargeId, $order->total_cents);
                $order->paymentTransactions()->create([
                    'provider' => 'pagbank',
                    'provider_transaction_id' => data_get($response, 'id', $chargeId),
                    'method' => $order->payment_method,
                    'status' => 'REFUNDED',
                    'amount_cents' => $order->total_cents,
                    'raw_payload' => $response,
                ]);
            }

            $order->forceFill([
                'status' => 'refunded',
                'payment_status' => 'refunded',
            ])->save();
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['refund' => 'Falha ao estornar no PagBank. Tente novamente.']);
        }

        return back()->with('success', 'Pedido estornado com sucesso.');
    }
}

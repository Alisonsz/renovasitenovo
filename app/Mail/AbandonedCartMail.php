<?php

namespace App\Mail;

use App\Models\Cart;
use App\Services\Store\CartRecoveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedCartMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public int $cartId)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Você esqueceu algo no carrinho 💧 ganhe um desconto especial',
        );
    }

    public function content(): Content
    {
        $cart = Cart::query()
            ->with(['items.product.images', 'recoveryCoupon'])
            ->findOrFail($this->cartId);

        $recovery = app(CartRecoveryService::class);

        return new Content(
            markdown: 'emails.cart.abandoned',
            with: [
                'cart' => $cart,
                'items' => $cart->items,
                'coupon' => $cart->recoveryCoupon,
                'discountPercent' => (int) config('cart.recovery_discount_percent'),
                'recoveryUrl' => $recovery->recoveryUrl($cart),
                'customerName' => $cart->customer_name,
            ],
        );
    }
}

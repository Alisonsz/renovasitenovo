<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public int $orderId) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pagamento confirmado — seu pedido na Renova Laser',
        );
    }

    public function content(): Content
    {
        $order = Order::query()
            ->with(['items', 'customer'])
            ->findOrFail($this->orderId);

        return new Content(
            markdown: 'emails.orders.confirmation',
            with: [
                'order' => $order,
                'items' => $order->items,
                'customerName' => $order->customer?->name,
            ],
        );
    }
}

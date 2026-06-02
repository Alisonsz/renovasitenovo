@php
    $fmt = fn ($cents) => 'R$ ' . number_format(((int) $cents) / 100, 2, ',', '.');
@endphp

<x-mail::message>
# Pagamento confirmado! 🎉

{{ $customerName ? "Olá, $customerName." : 'Olá.' }} Recebemos o pagamento do seu pedido **{{ $order->number }}**. Obrigada pela confiança!

## Resumo do pedido

<x-mail::table>
| Produto | Qtd | Valor |
|:------- |:---:| -----:|
@foreach ($items as $item)
| {{ $item->product_name }} | {{ $item->quantity }} | {{ $fmt($item->total_cents) }} |
@endforeach
</x-mail::table>

@if ($order->discount_cents)
**Desconto:** - {{ $fmt($order->discount_cents) }}<br>
@endif
@if ($order->pix_discount_cents)
**Desconto Pix:** - {{ $fmt($order->pix_discount_cents) }}<br>
@endif
**Total pago:** {{ $fmt($order->total_cents) }}

<x-mail::button :url="url('/minhas-compras')" color="success">
Ver minhas compras
</x-mail::button>

Em breve nossa equipe entrará em contato para os próximos passos. Qualquer dúvida, fale com a gente pelo WhatsApp.

Um abraço,<br>
Equipe **Renova Laser Depilação** — Tatuapé/SP
</x-mail::message>

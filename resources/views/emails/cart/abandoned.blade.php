@php
    use Illuminate\Support\Number;
    $fmt = fn ($cents) => 'R$ ' . number_format(((int) $cents) / 100, 2, ',', '.');
    $discounted = (int) floor($cart->total_cents * (1 - $discountPercent / 100));
@endphp

<x-mail::message>
# {{ $customerName ? "Oi, $customerName!" : 'Oi!' }}

Você deixou alguns itens no seu carrinho na **Renova Laser Depilação**. Eles ainda estão te esperando — e separamos um presente para você concluir agora.

<x-mail::panel>
🎁 **{{ $discountPercent }}% de desconto** no seu carrinho com o cupom **{{ strtoupper($coupon->code) }}**
@if ($coupon->expires_at)
<br><small>Válido até {{ $coupon->expires_at->format('d/m/Y H:i') }}</small>
@endif
</x-mail::panel>

## Seu carrinho

<x-mail::table>
| Produto | Qtd | Valor |
|:------- |:---:| -----:|
@foreach ($items as $item)
| {{ $item->product->name ?? $item->product_name }} | {{ $item->quantity }} | {{ $fmt($item->total_cents) }} |
@endforeach
</x-mail::table>

**Subtotal:** {{ $fmt($cart->total_cents) }}
**Com {{ $discountPercent }}% off:** {{ $fmt($discounted) }}

<x-mail::button :url="$recoveryUrl" color="success">
Recuperar meu carrinho com {{ $discountPercent }}% off
</x-mail::button>

O cupom já será aplicado automaticamente ao abrir o link.

Qualquer dúvida, fale com a gente pelo WhatsApp. Estamos aqui para te ajudar 💙

Obrigada,<br>
Equipe **Renova Laser Depilação** — Tatuapé/SP

<x-slot:subcopy>
Se o botão não funcionar, copie e cole este endereço no navegador:<br>
<a href="{{ $recoveryUrl }}">{{ $recoveryUrl }}</a>
</x-slot:subcopy>
</x-mail::message>

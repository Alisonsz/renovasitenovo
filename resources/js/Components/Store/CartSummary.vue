<script setup>
defineProps({
    cart: { type: Object, required: true },
});

function formatCents(cents) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format((cents || 0) / 100);
}
</script>

<template>
    <aside class="rounded-[4px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.12)] transition duration-300 ease-out hover:shadow-[0_12px_28px_rgba(0,0,0,0.12)]">
        <h2 class="font-poppins text-[21px] font-semibold text-[#363636]">Resumo do pedido</h2>

        <dl class="mt-6 space-y-4 font-montserrat text-[15px] text-[#555]">
            <div class="flex items-center justify-between">
                <dt>Subtotal</dt>
                <dd class="font-semibold text-[#333]">{{ formatCents(cart.subtotal_cents) }}</dd>
            </div>
            <div class="flex items-center justify-between">
                <dt>Descontos</dt>
                <dd class="font-semibold text-[#333]">- {{ formatCents(cart.discount_cents) }}</dd>
            </div>
            <div class="border-t border-[#e5e5e5] pt-4">
                <div class="flex items-center justify-between font-poppins text-[19px] font-semibold text-[#333]">
                    <dt>Total</dt>
                    <dd>{{ formatCents(cart.total_cents) }}</dd>
                </div>
            </div>
        </dl>

        <a
            href="/checkout"
            class="mt-6 flex h-[48px] items-center justify-center rounded-[3px] bg-brand px-5 font-poppins text-[16px] font-semibold text-white transition duration-200 hover:-translate-y-[2px] hover:brightness-105 hover:shadow-[0_10px_22px_rgba(41,216,219,0.25)] active:translate-y-0"
            :class="{ 'pointer-events-none opacity-50': !cart.items.length }"
        >
            Finalizar compra
        </a>
    </aside>
</template>

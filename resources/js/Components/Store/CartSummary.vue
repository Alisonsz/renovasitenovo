<script setup>
import { router, useForm } from '@inertiajs/vue3';

defineProps({
    cart: { type: Object, required: true },
    // Hidden on the checkout page itself (there it would just reload the page).
    showCheckout: { type: Boolean, default: true },
});

const couponForm = useForm({
    coupon: '',
});

function formatCents(cents) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format((cents || 0) / 100);
}

function applyCoupon() {
    couponForm.post('/carrinho/cupom', {
        preserveScroll: true,
        onSuccess: () => couponForm.reset('coupon'),
    });
}

function removeCoupon() {
    router.delete('/carrinho/cupom', { preserveScroll: true });
}
</script>

<template>
    <aside class="rounded-[8px] bg-white p-6 shadow-[0_10px_28px_rgba(0,0,0,0.10)] ring-1 ring-black/[0.03] transition duration-300 ease-out hover:shadow-[0_16px_34px_rgba(0,0,0,0.12)] lg:sticky lg:top-6">
        <h2 class="font-poppins text-[22px] font-extrabold text-[#363636]">Resumo do pedido</h2>

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

        <div class="mt-6 border-t border-[#e5e5e5] pt-5">
            <form v-if="!cart.coupon" class="flex gap-2" @submit.prevent="applyCoupon">
                <label class="sr-only" for="coupon-code">Cupom</label>
                <input
                    id="coupon-code"
                    v-model="couponForm.coupon"
                    type="text"
                    placeholder="Cupom"
                    class="h-[42px] min-w-0 flex-1 rounded-[4px] border border-[#dce5e5] px-3 font-montserrat text-[14px] uppercase outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20"
                >
                <button class="rounded-[4px] bg-[#eefafa] px-4 font-poppins text-[13px] font-semibold text-brand transition hover:bg-brand hover:text-white" :disabled="couponForm.processing">
                    Aplicar
                </button>
            </form>
            <p v-if="couponForm.errors.coupon" class="mt-2 font-montserrat text-[12px] text-red-600">{{ couponForm.errors.coupon }}</p>

            <div v-if="cart.coupon" class="flex items-center justify-between gap-3 rounded-[4px] bg-[#eefafa] px-3 py-2">
                <span class="font-montserrat text-[13px] font-semibold uppercase text-brand">{{ cart.coupon.code }}</span>
                <button type="button" class="font-poppins text-[12px] font-semibold text-[#777] transition hover:text-brand" @click="removeCoupon">
                    Remover
                </button>
            </div>
        </div>

        <a
            v-if="showCheckout"
            href="/checkout"
            class="mt-6 flex h-[50px] items-center justify-center rounded-[4px] bg-brand px-5 font-poppins text-[16px] font-semibold text-white transition duration-200 hover:-translate-y-[2px] hover:brightness-105 hover:shadow-[0_10px_22px_rgba(41,216,219,0.25)] active:translate-y-0"
            :class="{ 'pointer-events-none opacity-50': !cart.items.length }"
        >
            Finalizar compra
        </a>
    </aside>
</template>

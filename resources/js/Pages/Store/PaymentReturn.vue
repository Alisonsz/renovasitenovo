<script setup>
import { Head } from '@inertiajs/vue3';
import SiteLayout from '../../Layouts/SiteLayout.vue';
import { WHATSAPP } from '../../data/site.js';

defineProps({
    order: { type: Object, required: true },
});

function formatCents(cents) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format((cents || 0) / 100);
}
</script>

<template>
    <Head :title="`Pedido ${order.number}`" />

    <SiteLayout header-variant="store">
        <section class="bg-[#f7f7f7] px-5 py-[54px]">
            <div class="mx-auto max-w-[860px] rounded-[4px] bg-white p-8 text-center shadow-[0_0_10px_rgba(0,0,0,0.12)]">
                <span class="mx-auto grid h-[76px] w-[76px] place-items-center rounded-full bg-[#e8f8f8] text-brand">
                    <i class="fa-solid fa-clock text-[34px]"></i>
                </span>
                <h1 class="mt-5 font-poppins text-[30px] font-extrabold text-[#363636]">
                    Pedido recebido
                </h1>
                <p class="mt-3 font-montserrat text-[16px] leading-relaxed text-[#666]">
                    Seu pedido {{ order.number }} foi criado e está aguardando confirmação de pagamento.
                </p>

                <div class="mx-auto mt-7 max-w-[520px] rounded-[4px] bg-[#f7f7f7] p-5 text-left font-montserrat text-[15px] text-[#555]">
                    <div class="flex items-center justify-between">
                        <span>Status</span>
                        <strong class="font-poppins text-[#333]">{{ order.payment_status }}</strong>
                    </div>
                    <div class="mt-3 flex items-center justify-between border-t border-[#e0e0e0] pt-3">
                        <span>Total</span>
                        <strong class="font-poppins text-[#333]">{{ formatCents(order.total_cents) }}</strong>
                    </div>
                </div>

                <div class="mt-7 flex flex-wrap justify-center gap-3">
                    <a
                        v-if="order.pagbank_pay_url"
                        :href="order.pagbank_pay_url"
                        class="inline-flex h-[46px] items-center justify-center rounded-[3px] bg-brand px-6 font-poppins text-[15px] font-semibold text-white"
                    >
                        Abrir pagamento
                    </a>
                    <a
                        :href="WHATSAPP.atendimento"
                        class="inline-flex h-[46px] items-center justify-center rounded-[3px] border border-brand px-6 font-poppins text-[15px] font-semibold text-brand"
                    >
                        Falar com atendimento
                    </a>
                </div>
            </div>
        </section>
    </SiteLayout>
</template>

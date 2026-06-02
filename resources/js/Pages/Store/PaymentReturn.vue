<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import SiteLayout from '../../Layouts/SiteLayout.vue';
import { WHATSAPP } from '../../data/site.js';

const props = defineProps({
    order: { type: Object, required: true },
});

const paymentStatus = ref(props.order.payment_status);
const copied = ref(false);
let timer = null;

const isPaid = computed(() => paymentStatus.value === 'paid');
const isPix = computed(() => props.order.payment_method === 'pix' && props.order.pix);
const isPending = computed(() => ['pending', 'authorized', 'in_analysis'].includes(paymentStatus.value));

const statusLabel = computed(() => ({
    paid: 'Pagamento confirmado',
    pending: 'Aguardando pagamento',
    authorized: 'Pagamento em autorização',
    in_analysis: 'Pagamento em análise',
    declined: 'Pagamento recusado',
    failed: 'Pagamento não concluído',
    cancelled: 'Pedido cancelado',
}[paymentStatus.value] || paymentStatus.value));

function formatCents(cents) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format((cents || 0) / 100);
}

async function copyPix() {
    if (!props.order.pix?.text) return;
    await navigator.clipboard.writeText(props.order.pix.text);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
}

async function poll() {
    try {
        const res = await fetch(`/pedido/${props.order.number}/status`, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        paymentStatus.value = data.payment_status;
        if (data.paid && timer) {
            clearInterval(timer);
            timer = null;
        }
    } catch (e) {
        // network hiccup — keep polling
    }
}

onMounted(() => {
    if (isPending.value) {
        timer = setInterval(poll, 5000);
    }
});
onUnmounted(() => timer && clearInterval(timer));
</script>

<template>
    <Head :title="`Pedido ${order.number}`" />

    <SiteLayout header-variant="store">
        <section class="bg-[#f7f7f7] px-5 py-[54px]">
            <div class="mx-auto max-w-[860px] rounded-[8px] bg-white p-8 shadow-[0_0_10px_rgba(0,0,0,0.12)]">
                <!-- Header -->
                <div class="text-center">
                    <span class="mx-auto grid h-[76px] w-[76px] place-items-center rounded-full transition"
                        :class="isPaid ? 'bg-[#e6f9ee] text-green-600' : 'bg-[#e8f8f8] text-brand'">
                        <i :class="isPaid ? 'fa-solid fa-circle-check' : 'fa-solid fa-clock'" class="text-[34px]"></i>
                    </span>
                    <h1 class="mt-5 font-poppins text-[30px] font-extrabold text-[#363636]">
                        {{ isPaid ? 'Pagamento confirmado!' : 'Pedido recebido' }}
                    </h1>
                    <p class="mt-3 font-montserrat text-[16px] leading-relaxed text-[#666]">
                        Pedido <strong>{{ order.number }}</strong> — {{ statusLabel }}.
                    </p>
                </div>

                <!-- PIX QR -->
                <div v-if="isPix && !isPaid" class="mx-auto mt-8 max-w-[420px] rounded-[8px] border border-[#e0eaea] bg-[#fbffff] p-6 text-center">
                    <h2 class="font-poppins text-[18px] font-bold text-[#333]">Pague com Pix para confirmar</h2>
                    <img v-if="order.pix.png" :src="order.pix.png" alt="QR Code Pix" class="mx-auto mt-4 h-[220px] w-[220px] rounded-[4px] bg-white p-2 ring-1 ring-[#e0eaea]">
                    <div v-if="order.pix.text" class="mt-4">
                        <p class="font-montserrat text-[13px] text-muted">Pix copia e cola</p>
                        <div class="mt-2 flex items-center gap-2">
                            <input :value="order.pix.text" readonly class="h-[42px] flex-1 truncate rounded-[4px] border border-[#dce5e5] bg-white px-3 font-mono text-[12px] text-[#555]">
                            <button @click="copyPix" class="h-[42px] shrink-0 rounded-[4px] bg-brand px-4 font-poppins text-[13px] font-semibold text-white transition hover:brightness-105">
                                {{ copied ? 'Copiado!' : 'Copiar' }}
                            </button>
                        </div>
                    </div>
                    <p class="mt-4 inline-flex items-center gap-2 font-montserrat text-[13px] text-muted">
                        <i class="fa-solid fa-circle-notch fa-spin"></i> Aguardando confirmação automática…
                    </p>
                </div>

                <!-- Summary -->
                <div class="mx-auto mt-7 max-w-[520px] rounded-[8px] bg-[#f7f7f7] p-5 font-montserrat text-[15px] text-[#555]">
                    <div v-for="item in order.items" :key="item.product_name" class="flex items-center justify-between border-b border-[#e6e6e6] py-2 last:border-0">
                        <span>{{ item.quantity }}× {{ item.product_name }}</span>
                        <span>{{ formatCents(item.total_cents) }}</span>
                    </div>
                    <div class="mt-3 flex items-center justify-between border-t border-[#ddd] pt-3">
                        <span class="font-semibold">Total</span>
                        <strong class="font-poppins text-[16px] text-[#333]">{{ formatCents(order.total_cents) }}</strong>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-7 flex flex-wrap justify-center gap-3">
                    <a v-if="order.pagbank_pay_url && !isPaid" :href="order.pagbank_pay_url"
                        class="inline-flex h-[46px] items-center justify-center rounded-[3px] bg-brand px-6 font-poppins text-[15px] font-semibold text-white">Abrir pagamento</a>
                    <a href="/minhas-compras"
                        class="inline-flex h-[46px] items-center justify-center rounded-[3px] border border-brand px-6 font-poppins text-[15px] font-semibold text-brand-dark">Minhas compras</a>
                    <a :href="WHATSAPP.atendimento"
                        class="inline-flex h-[46px] items-center justify-center rounded-[3px] border border-[#dce5e5] px-6 font-poppins text-[15px] font-semibold text-[#666]">Falar com atendimento</a>
                </div>
            </div>
        </section>
    </SiteLayout>
</template>

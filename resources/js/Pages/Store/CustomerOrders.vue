<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import SiteLayout from '../../Layouts/SiteLayout.vue';

const props = defineProps({
    filters: { type: Object, required: true },
    searched: { type: Boolean, default: false },
    orders: { type: Array, default: () => [] },
});

const form = useForm({
    email: props.filters.email || '',
    document: props.filters.document || '',
});

function submit() {
    router.get('/minhas-compras', form.data(), {
        preserveState: true,
        replace: true,
    });
}

function formatCents(cents) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format((cents || 0) / 100);
}

function paymentLabel(status) {
    return {
        paid: 'Pago',
        pending: 'Pendente',
        failed: 'Falhou',
        cancelled: 'Cancelado',
    }[status] || status;
}
</script>

<template>
    <Head title="Minhas compras">
        <meta name="robots" content="noindex, nofollow">
    </Head>

    <SiteLayout header-variant="store">
        <section class="bg-[linear-gradient(180deg,#f8fbfb_0%,#f2f2f2_100%)] px-5 py-[44px] lg:py-[58px]">
            <div class="mx-auto max-w-[980px]">
                <h1 class="font-poppins text-[33px] font-extrabold text-[#363636]">Minhas compras</h1>
                <div class="mt-[18px] h-[2px] w-[91px] bg-brand"></div>

                <form class="mt-8 rounded-[8px] bg-white p-6 shadow-[0_10px_28px_rgba(0,0,0,0.10)] ring-1 ring-black/[0.03] lg:p-8" @submit.prevent="submit">
                    <h2 class="font-poppins text-[22px] font-extrabold text-[#363636]">Consultar pedidos</h2>
                    <p class="mt-1 font-montserrat text-[14px] text-muted">Informe o e-mail e CPF usados na compra.</p>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                            E-mail
                            <input v-model="form.email" type="email" required class="mt-2 h-[48px] w-full rounded-[4px] border border-[#dce5e5] bg-[#fbffff] px-3 font-normal outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                        </label>
                        <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                            CPF
                            <input v-model="form.document" type="text" required class="mt-2 h-[48px] w-full rounded-[4px] border border-[#dce5e5] bg-[#fbffff] px-3 font-normal outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                        </label>
                    </div>

                    <button class="mt-6 h-[50px] rounded-[4px] bg-brand px-7 font-poppins text-[16px] font-semibold text-white transition hover:-translate-y-[2px] hover:brightness-105 hover:shadow-[0_10px_22px_rgba(41,216,219,0.25)]">
                        Buscar pedidos
                    </button>
                </form>

                <div v-if="searched" class="mt-8 space-y-5">
                    <p v-if="!orders.length" class="rounded-[8px] bg-white px-6 py-8 text-center font-montserrat text-[16px] text-muted shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
                        Nenhum pedido encontrado com esses dados.
                    </p>

                    <article
                        v-for="order in orders"
                        :key="order.number"
                        class="rounded-[8px] bg-white p-6 shadow-[0_10px_28px_rgba(0,0,0,0.10)] ring-1 ring-black/[0.03]"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="font-montserrat text-[13px] font-bold uppercase tracking-[0.12em] text-brand-dark">Pedido {{ order.number }}</p>
                                <p class="mt-2 font-poppins text-[22px] font-extrabold text-heading">{{ formatCents(order.total_cents) }}</p>
                                <p class="mt-1 font-montserrat text-[14px] text-muted">Criado em {{ order.created_at }}</p>
                            </div>
                            <span class="rounded-full bg-[#edfafa] px-4 py-2 font-montserrat text-[13px] font-bold text-brand-dark">
                                {{ paymentLabel(order.payment_status) }}
                            </span>
                        </div>

                        <div class="mt-5 divide-y divide-[#edf0f0]">
                            <div v-for="item in order.items" :key="`${order.number}-${item.name}`" class="flex items-center gap-4 py-4">
                                <span class="grid h-[58px] w-[58px] shrink-0 place-items-center overflow-hidden rounded-[6px] bg-[#e3fbf8]">
                                    <img v-if="item.image_url" :src="item.image_url" :alt="item.name" class="h-full w-full object-cover">
                                    <i v-else class="fa-solid fa-spa text-brand"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-poppins text-[15px] font-extrabold leading-tight text-[#333]">{{ item.name }}</p>
                                    <p class="mt-1 font-montserrat text-[13px] text-muted">{{ item.quantity }} unidade(s)</p>
                                </div>
                                <p class="font-poppins text-[15px] font-semibold text-[#333]">{{ formatCents(item.total_cents) }}</p>
                            </div>
                        </div>

                        <a
                            v-if="order.pay_url && order.payment_status === 'pending'"
                            :href="order.pay_url"
                            class="mt-5 inline-flex h-[46px] items-center justify-center rounded-[4px] bg-brand px-6 font-poppins text-[15px] font-semibold text-white transition hover:brightness-105"
                        >
                            Concluir pagamento
                        </a>
                    </article>
                </div>
            </div>
        </section>
    </SiteLayout>
</template>

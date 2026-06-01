<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';

defineProps({
    metrics: { type: Object, required: true },
});

function formatCents(cents) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format((cents || 0) / 100);
}
</script>

<template>
    <Head title="Admin" />

    <AdminLayout>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-poppins text-[30px] font-extrabold text-[#363636]">Dashboard</h1>
                <p class="mt-1 font-montserrat text-[15px] text-[#777]">Resumo operacional da loja Renova Laser.</p>
            </div>
            <a href="/depilacao-feminina" class="rounded-[3px] bg-brand px-5 py-3 font-poppins text-[14px] font-semibold text-white transition hover:brightness-105">
                Ver loja
            </a>
        </div>

        <section class="mt-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                <p class="font-montserrat text-[13px] font-semibold uppercase text-[#777]">Produtos</p>
                <strong class="mt-3 block font-poppins text-[34px] text-[#363636]">{{ metrics.products }}</strong>
                <span class="font-montserrat text-[13px] text-brand">{{ metrics.active_products }} ativos</span>
            </article>
            <article class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                <p class="font-montserrat text-[13px] font-semibold uppercase text-[#777]">Categorias</p>
                <strong class="mt-3 block font-poppins text-[34px] text-[#363636]">{{ metrics.categories }}</strong>
                <span class="font-montserrat text-[13px] text-[#777]">WooCommerce importado</span>
            </article>
            <article class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                <p class="font-montserrat text-[13px] font-semibold uppercase text-[#777]">Pedidos</p>
                <strong class="mt-3 block font-poppins text-[34px] text-[#363636]">{{ metrics.orders }}</strong>
                <span class="font-montserrat text-[13px] text-brand">{{ metrics.pending_orders }} pendentes</span>
            </article>
            <article class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                <p class="font-montserrat text-[13px] font-semibold uppercase text-[#777]">Receita paga</p>
                <strong class="mt-3 block font-poppins text-[26px] text-[#363636]">{{ formatCents(metrics.revenue_cents) }}</strong>
                <span class="font-montserrat text-[13px] text-[#777]">{{ metrics.paid_orders }} pedidos pagos</span>
            </article>
        </section>
    </AdminLayout>
</template>

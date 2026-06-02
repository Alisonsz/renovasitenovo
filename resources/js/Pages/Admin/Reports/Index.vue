<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    summary: { type: Object, required: true },
    daily: { type: Array, default: () => [] },
    topProducts: { type: Array, default: () => [] },
    coupons: { type: Array, default: () => [] },
});

const fmt = (c) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format((c || 0) / 100);

const maxRevenue = computed(() => Math.max(1, ...props.daily.map((d) => d.revenue_cents)));
</script>

<template>
    <Head title="Relatórios" />
    <AdminLayout>
        <h1 class="font-poppins text-[30px] font-extrabold text-[#363636]">Relatórios</h1>
        <p class="mt-1 font-montserrat text-[15px] text-[#777]">Desempenho de vendas dos últimos 30 dias.</p>

        <section class="mt-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                <p class="font-montserrat text-[13px] font-semibold uppercase text-[#777]">Receita (30d)</p>
                <strong class="mt-3 block font-poppins text-[26px] text-[#363636]">{{ fmt(summary.revenue_30d_cents) }}</strong>
            </article>
            <article class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                <p class="font-montserrat text-[13px] font-semibold uppercase text-[#777]">Pedidos pagos (30d)</p>
                <strong class="mt-3 block font-poppins text-[34px] text-[#363636]">{{ summary.orders_30d }}</strong>
            </article>
            <article class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                <p class="font-montserrat text-[13px] font-semibold uppercase text-[#777]">Ticket médio</p>
                <strong class="mt-3 block font-poppins text-[26px] text-[#363636]">{{ fmt(summary.avg_ticket_cents) }}</strong>
            </article>
            <article class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                <p class="font-montserrat text-[13px] font-semibold uppercase text-[#777]">Receita total</p>
                <strong class="mt-3 block font-poppins text-[26px] text-[#363636]">{{ fmt(summary.total_revenue_cents) }}</strong>
            </article>
        </section>

        <section class="mt-6 rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
            <h2 class="font-poppins text-[17px] font-bold text-[#333]">Receita por dia</h2>
            <div v-if="daily.length" class="mt-5 flex h-[180px] items-end gap-1">
                <div v-for="d in daily" :key="d.day" class="group relative flex-1" :title="`${d.day}: ${fmt(d.revenue_cents)}`">
                    <div class="w-full rounded-t bg-brand/80 transition group-hover:bg-brand" :style="{ height: Math.max(2, (d.revenue_cents / maxRevenue) * 170) + 'px' }"></div>
                </div>
            </div>
            <p v-else class="mt-4 font-montserrat text-[14px] text-[#999]">Sem vendas no período.</p>
        </section>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <section class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                <h2 class="font-poppins text-[17px] font-bold text-[#333]">Produtos mais vendidos</h2>
                <table class="mt-4 w-full border-collapse font-montserrat text-[14px] text-[#555]">
                    <tbody>
                        <tr v-for="p in topProducts" :key="p.product_name" class="border-t border-[#eee]">
                            <td class="py-2">{{ p.product_name }}</td>
                            <td class="py-2 text-center text-[#888]">{{ p.qty }}x</td>
                            <td class="py-2 text-right font-semibold text-[#333]">{{ fmt(p.revenue_cents) }}</td>
                        </tr>
                        <tr v-if="!topProducts.length"><td class="py-4 text-center text-[#999]">Sem dados.</td></tr>
                    </tbody>
                </table>
            </section>

            <section class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                <h2 class="font-poppins text-[17px] font-bold text-[#333]">Desempenho de cupons</h2>
                <table class="mt-4 w-full border-collapse font-montserrat text-[14px] text-[#555]">
                    <tbody>
                        <tr v-for="c in coupons" :key="c.code" class="border-t border-[#eee]">
                            <td class="py-2 font-semibold uppercase text-[#333]">{{ c.code }}</td>
                            <td class="py-2 text-center text-[#888]">{{ c.uses }} usos</td>
                            <td class="py-2 text-right">- {{ fmt(c.discount_cents) }}</td>
                        </tr>
                        <tr v-if="!coupons.length"><td class="py-4 text-center text-[#999]">Nenhum cupom usado.</td></tr>
                    </tbody>
                </table>
            </section>
        </div>
    </AdminLayout>
</template>

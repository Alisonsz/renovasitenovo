<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineProps({
    orders: { type: Object, required: true },
});

function formatCents(cents) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format((cents || 0) / 100);
}
</script>

<template>
    <Head title="Pedidos" />

    <AdminLayout>
        <div>
            <h1 class="font-poppins text-[30px] font-extrabold text-[#363636]">Pedidos</h1>
            <p class="mt-1 font-montserrat text-[15px] text-[#777]">Acompanhe compras e status de pagamento.</p>
        </div>

        <div class="mt-8 overflow-hidden rounded-[6px] bg-white shadow-[0_0_10px_rgba(0,0,0,0.08)]">
            <table class="w-full min-w-[920px] border-collapse">
                <thead class="bg-[#f8fbfb] font-poppins text-[13px] uppercase text-[#777]">
                    <tr>
                        <th class="px-5 py-4 text-left">Pedido</th>
                        <th class="px-5 py-4 text-left">Cliente</th>
                        <th class="px-5 py-4 text-left">Itens</th>
                        <th class="px-5 py-4 text-left">Total</th>
                        <th class="px-5 py-4 text-left">Pagamento</th>
                        <th class="px-5 py-4 text-left">Criado em</th>
                        <th class="px-5 py-4 text-right">Link</th>
                    </tr>
                </thead>
                <tbody class="font-montserrat text-[14px] text-[#555]">
                    <tr v-for="order in orders.data" :key="order.id" class="border-t border-[#edf1f1] transition hover:bg-[#fbfefe]">
                        <td class="px-5 py-4">
                            <p class="font-poppins font-semibold text-[#333]">{{ order.number }}</p>
                            <p class="text-[12px] text-[#999]">{{ order.status }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-[#333]">{{ order.customer.name }}</p>
                            <p class="text-[12px] text-[#888]">{{ order.customer.email }}</p>
                        </td>
                        <td class="px-5 py-4">{{ order.items_count }}</td>
                        <td class="px-5 py-4 font-semibold text-[#333]">{{ formatCents(order.total_cents) }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full bg-[#eefafa] px-3 py-1 text-[12px] font-semibold text-brand">
                                {{ order.payment_status }}
                            </span>
                        </td>
                        <td class="px-5 py-4">{{ order.created_at }}</td>
                        <td class="px-5 py-4 text-right">
                            <a v-if="order.pay_url" :href="order.pay_url" target="_blank" rel="noreferrer" class="font-poppins text-[13px] font-semibold text-brand hover:underline">Abrir</a>
                        </td>
                    </tr>
                    <tr v-if="!orders.data.length">
                        <td colspan="7" class="px-5 py-10 text-center text-[#888]">Nenhum pedido encontrado.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

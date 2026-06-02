<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import Pagination from '../../../Components/Admin/Pagination.vue';

const props = defineProps({
    orders: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    paymentStatusOptions: { type: Array, default: () => [] },
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const paymentStatus = ref(props.filters.payment_status ?? '');

let t = null;
watch([search, status, paymentStatus], () => {
    clearTimeout(t);
    t = setTimeout(() => {
        router.get('/admin/orders', {
            search: search.value || undefined,
            status: status.value || undefined,
            payment_status: paymentStatus.value || undefined,
        }, { preserveState: true, replace: true });
    }, 300);
});

const fmt = (c) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format((c || 0) / 100);

const payBadge = (s) => ({
    paid: 'bg-green-50 text-green-700', pending: 'bg-amber-50 text-amber-700',
    refunded: 'bg-purple-50 text-purple-700', cancelled: 'bg-gray-100 text-gray-500',
    failed: 'bg-red-50 text-red-700', declined: 'bg-red-50 text-red-700',
}[s] || 'bg-[#eefafa] text-brand');
</script>

<template>
    <Head title="Pedidos" />
    <AdminLayout>
        <div>
            <h1 class="font-poppins text-[30px] font-extrabold text-[#363636]">Pedidos</h1>
            <p class="mt-1 font-montserrat text-[15px] text-[#777]">Acompanhe compras, status e estornos.</p>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-3">
            <input v-model="search" placeholder="Buscar nº, nome ou e-mail" class="h-[42px] rounded border border-[#dde6e6] px-3 font-montserrat text-[14px] outline-none focus:border-brand">
            <select v-model="status" class="h-[42px] rounded border border-[#dde6e6] px-3 font-montserrat text-[14px] outline-none focus:border-brand">
                <option value="">Todos os status</option>
                <option v-for="s in statusOptions" :key="s" :value="s">{{ s }}</option>
            </select>
            <select v-model="paymentStatus" class="h-[42px] rounded border border-[#dde6e6] px-3 font-montserrat text-[14px] outline-none focus:border-brand">
                <option value="">Todos os pagamentos</option>
                <option v-for="s in paymentStatusOptions" :key="s" :value="s">{{ s }}</option>
            </select>
        </div>

        <div class="mt-6 overflow-hidden rounded-[6px] bg-white shadow-[0_0_10px_rgba(0,0,0,0.08)]">
            <table class="w-full min-w-[860px] border-collapse">
                <thead class="bg-[#f8fbfb] font-poppins text-[13px] uppercase text-[#777]">
                    <tr>
                        <th class="px-5 py-4 text-left">Pedido</th>
                        <th class="px-5 py-4 text-left">Cliente</th>
                        <th class="px-5 py-4 text-left">Itens</th>
                        <th class="px-5 py-4 text-left">Total</th>
                        <th class="px-5 py-4 text-left">Pagamento</th>
                        <th class="px-5 py-4 text-left">Criado</th>
                        <th class="px-5 py-4 text-right">Ações</th>
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
                        <td class="px-5 py-4 font-semibold text-[#333]">{{ fmt(order.total_cents) }}</td>
                        <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-[12px] font-semibold" :class="payBadge(order.payment_status)">{{ order.payment_status }}</span></td>
                        <td class="px-5 py-4 text-[13px]">{{ order.created_at }}</td>
                        <td class="px-5 py-4 text-right">
                            <Link :href="`/admin/orders/${order.id}`" class="font-poppins text-[13px] font-semibold text-brand hover:underline">Ver</Link>
                        </td>
                    </tr>
                    <tr v-if="!orders.data.length"><td colspan="7" class="px-5 py-10 text-center text-[#888]">Nenhum pedido encontrado.</td></tr>
                </tbody>
            </table>
        </div>

        <Pagination :meta="orders" />
    </AdminLayout>
</template>

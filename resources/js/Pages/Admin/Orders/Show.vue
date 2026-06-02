<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    order: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
    paymentStatusOptions: { type: Array, default: () => [] },
    canRefund: { type: Boolean, default: false },
});

const statusForm = useForm({
    status: props.order.status,
    payment_status: props.order.payment_status,
});

const fmt = (c) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format((c || 0) / 100);

function saveStatus() {
    statusForm.put(`/admin/orders/${props.order.id}/status`, { preserveScroll: true });
}

function refund() {
    if (!confirm('Confirmar estorno deste pedido no PagBank? Esta ação não pode ser desfeita.')) return;
    router.post(`/admin/orders/${props.order.id}/refund`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Pedido ${order.number}`" />
    <AdminLayout>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <Link href="/admin/orders" class="font-montserrat text-[13px] text-brand hover:underline">← Pedidos</Link>
                <h1 class="mt-1 font-poppins text-[28px] font-extrabold text-[#363636]">Pedido {{ order.number }}</h1>
                <p class="font-montserrat text-[14px] text-[#777]">Criado em {{ order.created_at }}</p>
            </div>
        </div>

        <div class="mt-7 grid gap-6 lg:grid-cols-[1fr_340px]">
            <!-- Left: items + transactions -->
            <div class="space-y-6">
                <section class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                    <h2 class="font-poppins text-[17px] font-bold text-[#333]">Itens</h2>
                    <table class="mt-4 w-full border-collapse font-montserrat text-[14px] text-[#555]">
                        <tbody>
                            <tr v-for="(item, i) in order.items" :key="i" class="border-t border-[#eee]">
                                <td class="py-3">{{ item.quantity }}× {{ item.product_name }}</td>
                                <td class="py-3 text-right">{{ fmt(item.total_cents) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="mt-4 space-y-1 border-t border-[#eee] pt-4 font-montserrat text-[14px]">
                        <div class="flex justify-between text-[#777]"><span>Subtotal</span><span>{{ fmt(order.subtotal_cents) }}</span></div>
                        <div v-if="order.discount_cents" class="flex justify-between text-[#777]"><span>Desconto<span v-if="order.coupon"> ({{ order.coupon.code }})</span></span><span>- {{ fmt(order.discount_cents) }}</span></div>
                        <div v-if="order.pix_discount_cents" class="flex justify-between text-[#777]"><span>Desconto Pix</span><span>- {{ fmt(order.pix_discount_cents) }}</span></div>
                        <div class="flex justify-between font-poppins text-[16px] font-bold text-[#333]"><span>Total</span><span>{{ fmt(order.total_cents) }}</span></div>
                    </div>
                </section>

                <section class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                    <h2 class="font-poppins text-[17px] font-bold text-[#333]">Transações</h2>
                    <table class="mt-4 w-full border-collapse font-montserrat text-[13px] text-[#555]">
                        <thead class="text-left text-[12px] uppercase text-[#999]">
                            <tr><th class="py-2">ID</th><th class="py-2">Método</th><th class="py-2">Status</th><th class="py-2 text-right">Valor</th><th class="py-2 text-right">Data</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in order.transactions" :key="t.id" class="border-t border-[#eee]">
                                <td class="py-2 font-mono text-[12px]">{{ t.provider_transaction_id || '—' }}</td>
                                <td class="py-2">{{ t.method || '—' }}</td>
                                <td class="py-2">{{ t.status }}</td>
                                <td class="py-2 text-right">{{ fmt(t.amount_cents) }}</td>
                                <td class="py-2 text-right">{{ t.created_at }}</td>
                            </tr>
                            <tr v-if="!order.transactions.length"><td colspan="5" class="py-4 text-center text-[#999]">Sem transações.</td></tr>
                        </tbody>
                    </table>
                </section>
            </div>

            <!-- Right: customer + status controls -->
            <div class="space-y-6">
                <section class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                    <h2 class="font-poppins text-[17px] font-bold text-[#333]">Cliente</h2>
                    <div class="mt-3 space-y-1 font-montserrat text-[14px] text-[#555]">
                        <p class="font-semibold text-[#333]">{{ order.customer.name }}</p>
                        <p>{{ order.customer.email }}</p>
                        <p>{{ order.customer.phone }}</p>
                        <p>CPF: {{ order.customer.document }}</p>
                        <Link v-if="order.customer.id" :href="`/admin/customers/${order.customer.id}`" class="inline-block pt-2 text-[13px] font-semibold text-brand hover:underline">Ver cliente</Link>
                    </div>
                </section>

                <section class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                    <h2 class="font-poppins text-[17px] font-bold text-[#333]">Status</h2>
                    <div class="mt-4 space-y-4">
                        <label class="block font-montserrat text-[13px] font-semibold text-[#555]">
                            Pedido
                            <select v-model="statusForm.status" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                                <option v-for="s in statusOptions" :key="s" :value="s">{{ s }}</option>
                            </select>
                        </label>
                        <label class="block font-montserrat text-[13px] font-semibold text-[#555]">
                            Pagamento
                            <select v-model="statusForm.payment_status" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                                <option v-for="s in paymentStatusOptions" :key="s" :value="s">{{ s }}</option>
                            </select>
                        </label>
                        <button @click="saveStatus" :disabled="statusForm.processing" class="h-[42px] w-full rounded bg-brand font-poppins text-[14px] font-semibold text-white transition hover:brightness-105 disabled:opacity-60">Salvar status</button>
                    </div>
                </section>

                <section v-if="canRefund" class="rounded-[6px] border border-red-100 bg-red-50/40 p-6">
                    <h2 class="font-poppins text-[15px] font-bold text-red-700">Estorno</h2>
                    <p class="mt-1 font-montserrat text-[13px] text-red-600">Devolve o valor ao cliente via PagBank e marca o pedido como estornado.</p>
                    <button @click="refund" class="mt-4 h-[42px] w-full rounded border border-red-300 bg-white font-poppins text-[14px] font-semibold text-red-700 transition hover:bg-red-50">Estornar pedido</button>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>

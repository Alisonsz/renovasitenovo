<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineProps({
    coupons: { type: Object, required: true },
});

const form = useForm({
    code: '',
    type: 'fixed_cart',
    amount: '',
    percent: '',
    expires_at: '',
    usage_limit: '',
    is_active: true,
});

function submit() {
    form.post('/admin/coupons', {
        preserveScroll: true,
        onSuccess: () => form.reset('code', 'amount', 'percent', 'expires_at', 'usage_limit'),
    });
}

function formatDiscount(coupon) {
    if (coupon.type === 'percent') {
        return `${coupon.percent || 0}%`;
    }

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format((coupon.amount_cents || 0) / 100);
}
</script>

<template>
    <Head title="Cupons" />

    <AdminLayout>
        <div>
            <h1 class="font-poppins text-[30px] font-extrabold text-[#363636]">Cupons</h1>
            <p class="mt-1 font-montserrat text-[15px] text-[#777]">Crie descontos simples para o checkout.</p>
        </div>

        <form class="mt-8 grid gap-4 rounded-[6px] bg-white p-5 shadow-[0_0_10px_rgba(0,0,0,0.08)] lg:grid-cols-6" @submit.prevent="submit">
            <label class="block font-montserrat text-[13px] font-semibold text-[#555] lg:col-span-2">
                Codigo
                <input v-model="form.code" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 uppercase outline-none focus:border-brand">
            </label>
            <label class="block font-montserrat text-[13px] font-semibold text-[#555]">
                Tipo
                <select v-model="form.type" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                    <option value="fixed_cart">Valor</option>
                    <option value="percent">Percentual</option>
                </select>
            </label>
            <label class="block font-montserrat text-[13px] font-semibold text-[#555]">
                Valor
                <input v-model="form.amount" type="number" step="0.01" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
            </label>
            <label class="block font-montserrat text-[13px] font-semibold text-[#555]">
                Percentual
                <input v-model="form.percent" type="number" step="0.01" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
            </label>
            <label class="block font-montserrat text-[13px] font-semibold text-[#555]">
                Expira em
                <input v-model="form.expires_at" type="date" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
            </label>
            <label class="block font-montserrat text-[13px] font-semibold text-[#555]">
                Limite
                <input v-model="form.usage_limit" type="number" min="1" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
            </label>
            <label class="mt-7 flex items-center gap-2 font-montserrat text-[13px] font-semibold text-[#555]">
                <input v-model="form.is_active" type="checkbox" class="accent-brand">
                Ativo
            </label>
            <div class="flex items-end lg:col-span-4">
                <button class="rounded bg-brand px-6 py-3 font-poppins text-[14px] font-semibold text-white" :disabled="form.processing">
                    Criar cupom
                </button>
            </div>
        </form>

        <div class="mt-8 overflow-hidden rounded-[6px] bg-white shadow-[0_0_10px_rgba(0,0,0,0.08)]">
            <table class="w-full min-w-[760px] border-collapse">
                <thead class="bg-[#f8fbfb] font-poppins text-[13px] uppercase text-[#777]">
                    <tr>
                        <th class="px-5 py-4 text-left">Cupom</th>
                        <th class="px-5 py-4 text-left">Desconto</th>
                        <th class="px-5 py-4 text-left">Uso</th>
                        <th class="px-5 py-4 text-left">Expira</th>
                        <th class="px-5 py-4 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="font-montserrat text-[14px] text-[#555]">
                    <tr v-for="coupon in coupons.data" :key="coupon.id" class="border-t border-[#edf1f1] transition hover:bg-[#fbfefe]">
                        <td class="px-5 py-4 font-poppins font-semibold uppercase text-[#333]">{{ coupon.code }}</td>
                        <td class="px-5 py-4">{{ formatDiscount(coupon) }}</td>
                        <td class="px-5 py-4">{{ coupon.used_count }} / {{ coupon.usage_limit || 'sem limite' }}</td>
                        <td class="px-5 py-4">{{ coupon.expires_at || '-' }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-3 py-1 text-[12px] font-semibold" :class="coupon.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'">
                                {{ coupon.is_active ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                    </tr>
                    <tr v-if="!coupons.data.length">
                        <td colspan="5" class="px-5 py-10 text-center text-[#888]">Nenhum cupom encontrado.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

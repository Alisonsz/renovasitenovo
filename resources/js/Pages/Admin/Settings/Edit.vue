<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    settings: { type: Object, required: true },
    pagbank: { type: Object, required: true },
});

const form = useForm({
    store_name: props.settings.store_name ?? '',
    store_whatsapp: props.settings.store_whatsapp ?? '',
    store_email: props.settings.store_email ?? '',
    free_shipping_threshold: props.settings.free_shipping_threshold ?? '',
    flat_shipping_cents: props.settings.flat_shipping_cents ?? '',
    cart_recovery_enabled: !!props.settings.cart_recovery_enabled,
    cart_recovery_discount_percent: props.settings.cart_recovery_discount_percent ?? 10,
    cart_abandon_after_minutes: props.settings.cart_abandon_after_minutes ?? 60,
    pix_discount_percent: props.settings.pix_discount_percent ?? 5,
    max_installments: props.settings.max_installments ?? 12,
});

function save() {
    form.put('/admin/settings', { preserveScroll: true });
}

const credBadge = (set) => set ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700';
</script>

<template>
    <Head title="Configurações" />
    <AdminLayout>
        <h1 class="font-poppins text-[30px] font-extrabold text-[#363636]">Configurações</h1>
        <p class="mt-1 font-montserrat text-[15px] text-[#777]">Loja, frete, recuperação de carrinho e pagamento.</p>

        <form class="mt-8 space-y-6" @submit.prevent="save">
            <section class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                <h2 class="font-poppins text-[17px] font-bold text-[#333]">Loja</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <label class="block font-montserrat text-[13px] font-semibold text-[#555]">Nome
                        <input v-model="form.store_name" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand"></label>
                    <label class="block font-montserrat text-[13px] font-semibold text-[#555]">WhatsApp
                        <input v-model="form.store_whatsapp" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand"></label>
                    <label class="block font-montserrat text-[13px] font-semibold text-[#555]">E-mail
                        <input v-model="form.store_email" type="email" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand"></label>
                </div>
            </section>

            <section class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                <h2 class="font-poppins text-[17px] font-bold text-[#333]">Frete</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <label class="block font-montserrat text-[13px] font-semibold text-[#555]">Frete grátis acima de (R$)
                        <input v-model="form.free_shipping_threshold" type="number" step="0.01" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand"></label>
                    <label class="block font-montserrat text-[13px] font-semibold text-[#555]">Frete fixo (centavos)
                        <input v-model="form.flat_shipping_cents" type="number" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand"></label>
                </div>
            </section>

            <section class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                <h2 class="font-poppins text-[17px] font-bold text-[#333]">Recuperação de carrinho</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <label class="flex items-center gap-2 font-montserrat text-[13px] font-semibold text-[#555]">
                        <input v-model="form.cart_recovery_enabled" type="checkbox" class="accent-brand"> Ativar
                    </label>
                    <label class="block font-montserrat text-[13px] font-semibold text-[#555]">Desconto (%)
                        <input v-model="form.cart_recovery_discount_percent" type="number" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand"></label>
                    <label class="block font-montserrat text-[13px] font-semibold text-[#555]">Abandono após (min)
                        <input v-model="form.cart_abandon_after_minutes" type="number" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand"></label>
                </div>
            </section>

            <section class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                <h2 class="font-poppins text-[17px] font-bold text-[#333]">Pagamento (PagBank)</h2>
                <div class="mt-4 flex flex-wrap gap-2 font-montserrat text-[12px]">
                    <span class="rounded-full px-3 py-1 font-semibold" :class="credBadge(true)">Ambiente: {{ pagbank.env }}</span>
                    <span class="rounded-full px-3 py-1 font-semibold" :class="credBadge(pagbank.token_set)">Token {{ pagbank.token_set ? 'OK' : 'ausente' }}</span>
                    <span class="rounded-full px-3 py-1 font-semibold" :class="credBadge(pagbank.public_key_mode !== 'missing')">
                        Public key: {{ pagbank.public_key_mode === 'auto' ? 'automática' : (pagbank.public_key_mode === 'manual' ? 'manual' : 'ausente') }}
                    </span>
                    <span class="rounded-full px-3 py-1 font-semibold" :class="credBadge(pagbank.token_set)">Webhook: usa o token</span>
                </div>
                <p class="mt-2 font-montserrat text-[12px] text-[#999]">
                    Basta definir <strong>PAGBANK_TOKEN</strong> no .env. A chave pública do cartão é gerada automaticamente a partir do token e o webhook é validado com o mesmo token.
                </p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <label class="block font-montserrat text-[13px] font-semibold text-[#555]">Desconto Pix (%)
                        <input v-model="form.pix_discount_percent" type="number" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand"></label>
                    <label class="block font-montserrat text-[13px] font-semibold text-[#555]">Máx. parcelas
                        <input v-model="form.max_installments" type="number" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand"></label>
                </div>
            </section>

            <button :disabled="form.processing" class="h-[46px] rounded bg-brand px-8 font-poppins text-[15px] font-semibold text-white transition hover:brightness-105 disabled:opacity-60">Salvar configurações</button>
        </form>
    </AdminLayout>
</template>

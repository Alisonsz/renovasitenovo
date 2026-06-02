<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import SiteLayout from '../../Layouts/SiteLayout.vue';
import CartSummary from '../../Components/Store/CartSummary.vue';

const props = defineProps({
    cart: { type: Object, required: true },
    contact: { type: Object, default: () => ({ email: null, name: null }) },
    pixDiscountPercent: { type: Number, default: 0 },
    maxInstallments: { type: Number, default: 12 },
});

// Step 1 already done if we previously captured the email.
const step = ref(props.contact?.email ? 2 : 1);

const emailForm = useForm({
    email: props.contact?.email ?? '',
    name: props.contact?.name ?? '',
});

const form = useForm({
    name: props.contact?.name ?? '',
    email: props.contact?.email ?? '',
    phone: '',
    document: '',
    payment_method: 'pix',
});

const steps = [
    { n: 1, label: 'Identificação' },
    { n: 2, label: 'Seus dados' },
    { n: 3, label: 'Pagamento' },
];

const pixTotal = computed(() => {
    if (!props.pixDiscountPercent) return props.cart.total_cents;
    return Math.floor(props.cart.total_cents * (1 - props.pixDiscountPercent / 100));
});

const brl = (cents) => 'R$ ' + ((cents ?? 0) / 100).toFixed(2).replace('.', ',');

// Step 1 → captures email on the server (email-first) so the cart is recoverable.
function submitEmail() {
    emailForm.post('/checkout/identificacao', {
        preserveScroll: true,
        onSuccess: () => {
            form.email = emailForm.email;
            form.name = emailForm.name;
            step.value = 2;
        },
    });
}

function goToPayment() {
    // light client-side guard before showing payment step
    if (!form.name || !form.phone || !form.document) return;
    step.value = 3;
}

function submit() {
    router.post('/checkout', form);
}
</script>

<template>
    <Head title="Checkout" />

    <SiteLayout header-variant="store">
        <section class="bg-[linear-gradient(180deg,#f8fbfb_0%,#f2f2f2_100%)] px-5 py-[44px] lg:py-[58px]">
            <div class="mx-auto max-w-[1140px]">
                <h1 class="font-poppins text-[33px] font-extrabold text-[#363636]">Finalizar compra</h1>
                <div class="mt-[18px] h-[2px] w-[91px] bg-brand"></div>

                <!-- Stepper -->
                <ol class="mt-7 flex items-center gap-2 sm:gap-4">
                    <li v-for="(s, i) in steps" :key="s.n" class="flex items-center gap-2 sm:gap-4">
                        <div class="flex items-center gap-2">
                            <span
                                class="grid h-8 w-8 place-items-center rounded-full font-poppins text-[14px] font-bold transition"
                                :class="step >= s.n ? 'bg-brand text-white' : 'bg-white text-[#9aa] ring-1 ring-[#dce5e5]'"
                            >{{ s.n }}</span>
                            <span
                                class="hidden font-montserrat text-[13px] font-semibold sm:inline"
                                :class="step >= s.n ? 'text-[#333]' : 'text-[#9aa]'"
                            >{{ s.label }}</span>
                        </div>
                        <span v-if="i < steps.length - 1" class="h-px w-6 bg-[#dce5e5] sm:w-10"></span>
                    </li>
                </ol>

                <div class="mt-[28px] grid gap-8 lg:grid-cols-[1fr_330px]">
                    <div class="rounded-[8px] bg-white p-6 shadow-[0_10px_28px_rgba(0,0,0,0.10)] ring-1 ring-black/[0.03] lg:p-8">
                        <p v-if="form.errors.cart || emailForm.errors.cart" class="mb-5 rounded-[4px] bg-red-50 px-4 py-3 font-montserrat text-[14px] text-red-700">
                            {{ form.errors.cart || emailForm.errors.cart }}
                        </p>

                        <!-- STEP 1 — e-mail first -->
                        <div v-show="step === 1">
                            <h2 class="font-poppins text-[22px] font-extrabold text-[#363636]">Vamos começar</h2>
                            <p class="mt-1 font-montserrat text-[14px] text-muted">
                                Informe seu e-mail para iniciar. Guardamos seu carrinho para você não perder nada.
                            </p>
                            <form class="mt-6 space-y-5" @submit.prevent="submitEmail">
                                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                                    E-mail
                                    <input v-model="emailForm.email" type="email" required autocomplete="email"
                                        class="mt-2 h-[48px] w-full rounded-[4px] border border-[#dce5e5] bg-[#fbffff] px-3 font-normal outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                                    <span v-if="emailForm.errors.email" class="mt-1 block text-[12px] text-red-600">{{ emailForm.errors.email }}</span>
                                </label>
                                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                                    Nome (opcional)
                                    <input v-model="emailForm.name" type="text" autocomplete="name"
                                        class="mt-2 h-[48px] w-full rounded-[4px] border border-[#dce5e5] bg-[#fbffff] px-3 font-normal outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                                </label>
                                <button type="submit" :disabled="emailForm.processing || !cart.items.length"
                                    class="h-[52px] w-full rounded-[4px] bg-brand px-7 font-poppins text-[16px] font-semibold text-white transition hover:-translate-y-[2px] hover:brightness-105 disabled:opacity-60 sm:w-auto">
                                    Continuar
                                </button>
                            </form>
                        </div>

                        <!-- STEP 2 — buyer details -->
                        <div v-show="step === 2">
                            <h2 class="font-poppins text-[22px] font-extrabold text-[#363636]">Seus dados</h2>
                            <p class="mt-1 font-montserrat text-[14px] text-muted">{{ form.email }}</p>
                            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                                    Nome completo
                                    <input v-model="form.name" type="text"
                                        class="mt-2 h-[48px] w-full rounded-[4px] border border-[#dce5e5] bg-[#fbffff] px-3 font-normal outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                                    <span v-if="form.errors.name" class="mt-1 block text-[12px] text-red-600">{{ form.errors.name }}</span>
                                </label>
                                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                                    Telefone
                                    <input v-model="form.phone" type="tel"
                                        class="mt-2 h-[48px] w-full rounded-[4px] border border-[#dce5e5] bg-[#fbffff] px-3 font-normal outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                                    <span v-if="form.errors.phone" class="mt-1 block text-[12px] text-red-600">{{ form.errors.phone }}</span>
                                </label>
                                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                                    CPF
                                    <input v-model="form.document" type="text"
                                        class="mt-2 h-[48px] w-full rounded-[4px] border border-[#dce5e5] bg-[#fbffff] px-3 font-normal outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                                    <span v-if="form.errors.document" class="mt-1 block text-[12px] text-red-600">{{ form.errors.document }}</span>
                                </label>
                            </div>
                            <div class="mt-7 flex gap-3">
                                <button type="button" @click="step = 1"
                                    class="h-[52px] rounded-[4px] px-5 font-poppins text-[15px] font-semibold text-[#777] ring-1 ring-[#dce5e5] transition hover:bg-[#f5f5f5]">
                                    Voltar
                                </button>
                                <button type="button" @click="goToPayment" :disabled="!form.name || !form.phone || !form.document"
                                    class="h-[52px] flex-1 rounded-[4px] bg-brand px-7 font-poppins text-[16px] font-semibold text-white transition hover:-translate-y-[2px] hover:brightness-105 disabled:opacity-60 sm:flex-none">
                                    Ir para pagamento
                                </button>
                            </div>
                        </div>

                        <!-- STEP 3 — payment method (fully wired in Phase 3) -->
                        <div v-show="step === 3">
                            <h2 class="font-poppins text-[22px] font-extrabold text-[#363636]">Pagamento</h2>
                            <div class="mt-5 space-y-3">
                                <label class="flex cursor-pointer items-center gap-3 rounded-[8px] border px-4 py-4 font-montserrat text-[15px] text-[#333] transition"
                                    :class="form.payment_method === 'pix' ? 'border-brand bg-[#eefafa]' : 'border-[#dce5e5] hover:bg-[#f8fbfb]'">
                                    <input v-model="form.payment_method" type="radio" value="pix" class="accent-brand">
                                    <span class="flex-1">
                                        <strong class="block font-poppins text-[#333]">Pix</strong>
                                        Aprovação imediata
                                        <span v-if="pixDiscountPercent" class="font-semibold text-brand-dark"> — {{ pixDiscountPercent }}% de desconto ({{ brl(pixTotal) }})</span>
                                    </span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-3 rounded-[8px] border px-4 py-4 font-montserrat text-[15px] text-[#333] transition"
                                    :class="form.payment_method === 'credit_card' ? 'border-brand bg-[#eefafa]' : 'border-[#dce5e5] hover:bg-[#f8fbfb]'">
                                    <input v-model="form.payment_method" type="radio" value="credit_card" class="accent-brand">
                                    <span class="flex-1">
                                        <strong class="block font-poppins text-[#333]">Cartão de crédito</strong>
                                        Em até {{ maxInstallments }}x
                                    </span>
                                </label>
                            </div>

                            <p class="mt-4 rounded-[4px] bg-[#f8fbfb] px-4 py-3 font-montserrat text-[13px] text-muted">
                                🔒 Os campos do cartão e o QR Code do Pix serão habilitados na próxima etapa (integração transparente PagBank).
                            </p>

                            <div class="mt-7 flex gap-3">
                                <button type="button" @click="step = 2"
                                    class="h-[52px] rounded-[4px] px-5 font-poppins text-[15px] font-semibold text-[#777] ring-1 ring-[#dce5e5] transition hover:bg-[#f5f5f5]">
                                    Voltar
                                </button>
                                <button type="button" @click="submit" :disabled="form.processing || !cart.items.length"
                                    class="h-[52px] flex-1 rounded-[4px] bg-brand px-7 font-poppins text-[16px] font-semibold text-white transition hover:-translate-y-[2px] hover:brightness-105 disabled:opacity-60">
                                    Finalizar pedido
                                </button>
                            </div>
                        </div>
                    </div>

                    <CartSummary :cart="cart" />
                </div>
            </div>
        </section>
    </SiteLayout>
</template>

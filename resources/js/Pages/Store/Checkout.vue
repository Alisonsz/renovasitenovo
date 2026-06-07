<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import SiteLayout from '../../Layouts/SiteLayout.vue';
import CartSummary from '../../Components/Store/CartSummary.vue';
import { encryptCard, installmentOptions } from '../../Composables/usePagBank.js';

const props = defineProps({
    cart: { type: Object, required: true },
    contact: { type: Object, default: () => ({ email: null, name: null }) },
    pagbank: { type: Object, default: () => ({}) },
});

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
    card: { encrypted: '', holder: '', installments: 1 },
});

// Local-only card fields (never sent raw; encrypted client-side first).
const card = ref({ number: '', holder: '', expMonth: '', expYear: '', cvv: '' });
const cardError = ref('');
const processing = ref(false);

const steps = [
    { n: 1, label: 'Identificação' },
    { n: 2, label: 'Seus dados' },
    { n: 3, label: 'Pagamento' },
];

const pixPercent = computed(() => props.pagbank?.pix_discount_percent ?? 0);
const pixTotal = computed(() =>
    pixPercent.value ? Math.floor(props.cart.total_cents * (1 - pixPercent.value / 100)) : props.cart.total_cents
);
const installments = computed(() =>
    installmentOptions(
        props.cart.total_cents,
        props.pagbank?.max_installments ?? 12,
        props.pagbank?.min_installment_cents ?? 0
    )
);
const brl = (cents) => 'R$ ' + ((cents ?? 0) / 100).toFixed(2).replace('.', ',');

// --- Input masks (display only; we send clean digits on submit) ---
function maskPhone(v) {
    const d = (v || '').replace(/\D/g, '').slice(0, 11);
    if (d.length === 0) return '';
    if (d.length <= 2) return `(${d}`;
    if (d.length <= 6) return `(${d.slice(0, 2)}) ${d.slice(2)}`;
    if (d.length <= 10) return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`;
    return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`;
}
function maskCpf(v) {
    const d = (v || '').replace(/\D/g, '').slice(0, 11);
    if (d.length > 9) return `${d.slice(0, 3)}.${d.slice(3, 6)}.${d.slice(6, 9)}-${d.slice(9)}`;
    if (d.length > 6) return `${d.slice(0, 3)}.${d.slice(3, 6)}.${d.slice(6)}`;
    if (d.length > 3) return `${d.slice(0, 3)}.${d.slice(3)}`;
    return d;
}
const onlyDigits = (v) => (v || '').replace(/\D/g, '');

// --- Validation gates ---
const detailsReady = computed(() =>
    !!form.name.trim() && onlyDigits(form.phone).length >= 10 && onlyDigits(form.document).length === 11
);
const cardReady = computed(() =>
    form.payment_method !== 'credit_card' ||
    (card.value.number && card.value.holder && card.value.expMonth && card.value.expYear && card.value.cvv)
);
const canSubmit = computed(() => !!props.cart.items.length && detailsReady.value && cardReady.value);
const busy = computed(() => processing.value || form.processing);

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
    if (!detailsReady.value) return;
    step.value = 3;
}

async function submit() {
    cardError.value = '';
    if (!canSubmit.value || busy.value) return;

    if (form.payment_method === 'credit_card') {
        if (!props.pagbank?.public_key) {
            cardError.value = 'Pagamento com cartão indisponível no momento. Tente o Pix.';
            return;
        }
        processing.value = true;
        const { encrypted, errors } = await encryptCard(props.pagbank.public_key, {
            holder: card.value.holder,
            number: card.value.number,
            expMonth: card.value.expMonth,
            expYear: card.value.expYear,
            securityCode: card.value.cvv,
        }).catch((e) => ({ encrypted: null, errors: [{ message: e.message }] }));
        processing.value = false;

        if (!encrypted) {
            cardError.value = errors?.[0]?.message || 'Não foi possível validar o cartão.';
            return;
        }
        form.card.encrypted = encrypted;
        form.card.holder = card.value.holder;
    }

    // Use form.post so server-side validation/payment errors populate form.errors
    // (and are shown). Send clean digits for phone/CPF.
    form
        .transform((data) => ({
            ...data,
            phone: onlyDigits(data.phone),
            document: onlyDigits(data.document),
        }))
        .post('/checkout', {
            preserveScroll: true,
            onError: (errs) => {
                cardError.value = errs.payment || errs.card?.encrypted || errs.cart || '';
                // Field errors live on step 2 — bring the user back so they see them.
                if (errs.name || errs.phone || errs.document || errs.email) {
                    step.value = 2;
                }
            },
        });
}
</script>

<template>
    <Head title="Checkout" />

    <SiteLayout header-variant="store">
        <section class="bg-[linear-gradient(180deg,#f8fbfb_0%,#f2f2f2_100%)] px-5 py-[44px] lg:py-[58px]">
            <div class="mx-auto max-w-[1140px]">
                <h1 class="font-poppins text-[33px] font-extrabold text-[#363636]">Finalizar compra</h1>
                <div class="mt-[18px] h-[2px] w-[91px] bg-brand"></div>

                <ol class="mt-7 flex items-center gap-2 sm:gap-4">
                    <li v-for="(s, i) in steps" :key="s.n" class="flex items-center gap-2 sm:gap-4">
                        <div class="flex items-center gap-2">
                            <span class="grid h-8 w-8 place-items-center rounded-full font-poppins text-[14px] font-bold transition"
                                :class="step >= s.n ? 'bg-brand text-white' : 'bg-white text-[#9aa] ring-1 ring-[#dce5e5]'">{{ s.n }}</span>
                            <span class="hidden font-montserrat text-[13px] font-semibold sm:inline"
                                :class="step >= s.n ? 'text-[#333]' : 'text-[#9aa]'">{{ s.label }}</span>
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
                            <p class="mt-1 font-montserrat text-[14px] text-muted">Informe seu e-mail para iniciar. Guardamos seu carrinho para você não perder nada.</p>
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
                                    <input v-model="form.name" type="text" class="mt-2 h-[48px] w-full rounded-[4px] border border-[#dce5e5] bg-[#fbffff] px-3 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                                    <span v-if="form.errors.name" class="mt-1 block text-[12px] text-red-600">{{ form.errors.name }}</span>
                                </label>
                                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                                    Telefone
                                    <input :value="form.phone" @input="form.phone = maskPhone($event.target.value)" type="tel" inputmode="numeric" autocomplete="tel" maxlength="16" placeholder="(11) 99999-9999" class="mt-2 h-[48px] w-full rounded-[4px] border border-[#dce5e5] bg-[#fbffff] px-3 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                                    <span v-if="form.errors.phone" class="mt-1 block text-[12px] text-red-600">{{ form.errors.phone }}</span>
                                </label>
                                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                                    CPF
                                    <input :value="form.document" @input="form.document = maskCpf($event.target.value)" type="text" inputmode="numeric" maxlength="14" placeholder="000.000.000-00" class="mt-2 h-[48px] w-full rounded-[4px] border border-[#dce5e5] bg-[#fbffff] px-3 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                                    <span v-if="form.errors.document" class="mt-1 block text-[12px] text-red-600">{{ form.errors.document }}</span>
                                </label>
                            </div>
                            <div class="mt-7 flex gap-3">
                                <button type="button" @click="step = 1" class="h-[52px] rounded-[4px] px-5 font-poppins text-[15px] font-semibold text-[#777] ring-1 ring-[#dce5e5] transition hover:bg-[#f5f5f5]">Voltar</button>
                                <button type="button" @click="goToPayment" :disabled="!detailsReady"
                                    class="h-[52px] flex-1 rounded-[4px] bg-brand px-7 font-poppins text-[16px] font-semibold text-white transition hover:-translate-y-[2px] hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-60 sm:flex-none">Ir para pagamento</button>
                            </div>
                        </div>

                        <!-- STEP 3 — transparent payment -->
                        <div v-show="step === 3">
                            <h2 class="font-poppins text-[22px] font-extrabold text-[#363636]">Pagamento</h2>

                            <div class="mt-5 grid grid-cols-2 gap-3">
                                <button type="button" @click="form.payment_method = 'pix'"
                                    class="rounded-[8px] border px-4 py-3 text-left font-montserrat transition"
                                    :class="form.payment_method === 'pix' ? 'border-brand bg-[#eefafa]' : 'border-[#dce5e5] hover:bg-[#f8fbfb]'">
                                    <i class="fa-brands fa-pix text-[20px] text-brand-dark"></i>
                                    <strong class="mt-1 block font-poppins text-[15px] text-[#333]">Pix</strong>
                                    <span v-if="pixPercent" class="text-[12px] font-semibold text-brand-dark">{{ pixPercent }}% off</span>
                                </button>
                                <button type="button" @click="form.payment_method = 'credit_card'"
                                    class="rounded-[8px] border px-4 py-3 text-left font-montserrat transition"
                                    :class="form.payment_method === 'credit_card' ? 'border-brand bg-[#eefafa]' : 'border-[#dce5e5] hover:bg-[#f8fbfb]'">
                                    <i class="fa-solid fa-credit-card text-[20px] text-brand-dark"></i>
                                    <strong class="mt-1 block font-poppins text-[15px] text-[#333]">Cartão</strong>
                                    <span class="text-[12px] text-muted">até {{ pagbank.max_installments }}x</span>
                                </button>
                            </div>

                            <!-- PIX panel -->
                            <div v-show="form.payment_method === 'pix'" class="mt-5 rounded-[8px] bg-[#f8fbfb] p-5 font-montserrat text-[14px] text-[#555]">
                                <p>Ao confirmar, geramos um <strong>QR Code Pix</strong> para pagamento imediato. Você recebe a confirmação automaticamente.</p>
                                <p class="mt-2 text-[15px]">Total no Pix: <strong class="text-brand-dark">{{ brl(pixTotal) }}</strong></p>
                            </div>

                            <!-- Card panel -->
                            <div v-show="form.payment_method === 'credit_card'" class="mt-5 grid gap-4">
                                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                                    Número do cartão
                                    <input v-model="card.number" inputmode="numeric" autocomplete="cc-number" placeholder="0000 0000 0000 0000"
                                        class="mt-2 h-[48px] w-full rounded-[4px] border border-[#dce5e5] bg-white px-3 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                                </label>
                                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                                    Nome impresso no cartão
                                    <input v-model="card.holder" autocomplete="cc-name" placeholder="Como está no cartão"
                                        class="mt-2 h-[48px] w-full rounded-[4px] border border-[#dce5e5] bg-white px-3 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                                </label>
                                <div class="grid grid-cols-3 gap-3">
                                    <label class="block font-montserrat text-[13px] font-semibold text-[#555]">
                                        Mês
                                        <input v-model="card.expMonth" inputmode="numeric" placeholder="MM" maxlength="2"
                                            class="mt-2 h-[48px] w-full rounded-[4px] border border-[#dce5e5] bg-white px-3 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                                    </label>
                                    <label class="block font-montserrat text-[13px] font-semibold text-[#555]">
                                        Ano
                                        <input v-model="card.expYear" inputmode="numeric" placeholder="AAAA" maxlength="4"
                                            class="mt-2 h-[48px] w-full rounded-[4px] border border-[#dce5e5] bg-white px-3 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                                    </label>
                                    <label class="block font-montserrat text-[13px] font-semibold text-[#555]">
                                        CVV
                                        <input v-model="card.cvv" inputmode="numeric" autocomplete="cc-csc" placeholder="123" maxlength="4"
                                            class="mt-2 h-[48px] w-full rounded-[4px] border border-[#dce5e5] bg-white px-3 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                                    </label>
                                </div>
                                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                                    Parcelas
                                    <select v-model.number="form.card.installments"
                                        class="mt-2 h-[48px] w-full rounded-[4px] border border-[#dce5e5] bg-white px-3 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                                        <option v-for="opt in installments" :key="opt.n" :value="opt.n">{{ opt.label }}</option>
                                    </select>
                                </label>
                                <p class="font-montserrat text-[12px] text-muted">🔒 Seus dados são criptografados no seu navegador antes do envio.</p>
                            </div>

                            <p v-if="cardError" class="mt-4 rounded-[4px] bg-red-50 px-4 py-3 font-montserrat text-[14px] text-red-700">{{ cardError }}</p>

                            <div class="mt-7 flex gap-3">
                                <button type="button" @click="step = 2" class="h-[52px] rounded-[4px] px-5 font-poppins text-[15px] font-semibold text-[#777] ring-1 ring-[#dce5e5] transition hover:bg-[#f5f5f5]">Voltar</button>
                                <button type="button" @click="submit" :disabled="busy || !canSubmit"
                                    class="h-[52px] flex-1 rounded-[4px] bg-brand px-7 font-poppins text-[16px] font-semibold text-white transition hover:-translate-y-[2px] hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-60">
                                    <span v-if="busy">Processando…</span>
                                    <span v-else>{{ form.payment_method === 'pix' ? 'Gerar Pix' : 'Pagar agora' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <CartSummary :cart="cart" :show-checkout="false" />
                </div>
            </div>
        </section>
    </SiteLayout>
</template>

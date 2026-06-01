<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import SiteLayout from '../../Layouts/SiteLayout.vue';
import CartSummary from '../../Components/Store/CartSummary.vue';

const props = defineProps({
    cart: { type: Object, required: true },
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    document: '',
    payment_method: 'pagbank_checkout',
});

function submit() {
    router.post('/checkout', form);
}
</script>

<template>
    <Head title="Checkout" />

    <SiteLayout header-variant="store">
        <section class="bg-[#f7f7f7] px-5 py-[44px]">
            <div class="mx-auto max-w-[1140px]">
                <h1 class="font-poppins text-[33px] font-extrabold text-[#363636]">Finalizar compra</h1>
                <div class="mt-[22px] h-[2px] w-[91px] bg-brand"></div>

                <div class="mt-[34px] grid gap-8 lg:grid-cols-[1fr_330px]">
                    <form class="rounded-[4px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.12)]" @submit.prevent="submit">
                        <p v-if="form.errors.cart" class="mb-5 rounded-[3px] bg-red-50 px-4 py-3 font-montserrat text-[14px] text-red-700">
                            {{ form.errors.cart }}
                        </p>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                                Nome completo
                                <input v-model="form.name" type="text" class="mt-2 h-[45px] w-full rounded-[3px] border border-[#ddd] px-3 font-normal outline-none focus:border-brand">
                                <span v-if="form.errors.name" class="mt-1 block text-[12px] text-red-600">{{ form.errors.name }}</span>
                            </label>
                            <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                                E-mail
                                <input v-model="form.email" type="email" class="mt-2 h-[45px] w-full rounded-[3px] border border-[#ddd] px-3 font-normal outline-none focus:border-brand">
                                <span v-if="form.errors.email" class="mt-1 block text-[12px] text-red-600">{{ form.errors.email }}</span>
                            </label>
                            <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                                Telefone
                                <input v-model="form.phone" type="tel" class="mt-2 h-[45px] w-full rounded-[3px] border border-[#ddd] px-3 font-normal outline-none focus:border-brand">
                                <span v-if="form.errors.phone" class="mt-1 block text-[12px] text-red-600">{{ form.errors.phone }}</span>
                            </label>
                            <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                                CPF
                                <input v-model="form.document" type="text" class="mt-2 h-[45px] w-full rounded-[3px] border border-[#ddd] px-3 font-normal outline-none focus:border-brand">
                                <span v-if="form.errors.document" class="mt-1 block text-[12px] text-red-600">{{ form.errors.document }}</span>
                            </label>
                        </div>

                        <fieldset class="mt-7">
                            <legend class="font-poppins text-[18px] font-semibold text-[#363636]">Pagamento</legend>
                            <label class="mt-4 flex cursor-pointer items-center gap-3 rounded-[4px] border border-brand bg-[#eefafa] px-4 py-4 font-montserrat text-[15px] text-[#333]">
                                <input v-model="form.payment_method" type="radio" value="pagbank_checkout" class="accent-brand">
                                PagBank: Pix ou cartão de crédito em ambiente seguro
                            </label>
                        </fieldset>

                        <button
                            type="submit"
                            class="mt-7 h-[50px] rounded-[3px] bg-brand px-7 font-poppins text-[16px] font-semibold text-white transition hover:brightness-105 disabled:opacity-60"
                            :disabled="form.processing || !cart.items.length"
                        >
                            Continuar para pagamento
                        </button>
                    </form>

                    <CartSummary :cart="cart" />
                </div>
            </div>
        </section>
    </SiteLayout>
</template>

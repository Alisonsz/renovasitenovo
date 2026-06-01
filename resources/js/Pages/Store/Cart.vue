<script setup>
import { Head, router } from '@inertiajs/vue3';
import SiteLayout from '../../Layouts/SiteLayout.vue';
import CartSummary from '../../Components/Store/CartSummary.vue';
import QuantityInput from '../../Components/Store/QuantityInput.vue';

defineProps({
    cart: { type: Object, required: true },
});

function formatCents(cents) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format((cents || 0) / 100);
}

function removeItem(item) {
    router.delete(`/carrinho/items/${item.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Carrinho" />

    <SiteLayout header-variant="store">
        <section class="bg-[#f7f7f7] px-5 py-[44px]">
            <div class="mx-auto max-w-[1140px]">
                <h1 class="font-poppins text-[33px] font-extrabold text-[#363636]">Carrinho</h1>
                <div class="mt-[22px] h-[2px] w-[91px] bg-brand"></div>

                <div v-if="cart.items.length" class="mt-[34px] grid gap-8 lg:grid-cols-[1fr_330px]">
                    <div class="overflow-hidden rounded-[4px] bg-white shadow-[0_0_10px_rgba(0,0,0,0.12)] transition duration-300 hover:shadow-[0_12px_28px_rgba(0,0,0,0.10)]">
                        <div class="hidden grid-cols-[1fr_130px_150px_130px_48px] border-b border-[#ececec] px-5 py-4 font-poppins text-[14px] font-semibold uppercase text-[#777] lg:grid">
                            <span>Produto</span>
                            <span>Preço</span>
                            <span>Quantidade</span>
                            <span>Total</span>
                            <span></span>
                        </div>

                        <article
                            v-for="item in cart.items"
                            :key="item.id"
                            class="grid gap-4 border-b border-[#ececec] px-5 py-5 transition duration-200 last:border-0 hover:bg-[#fbfefe] lg:grid-cols-[1fr_130px_150px_130px_48px] lg:items-center"
                        >
                            <a :href="`/produto/${item.product.slug}`" class="flex items-center gap-4">
                                <span class="grid h-[82px] w-[82px] shrink-0 place-items-center overflow-hidden rounded-[4px] bg-[#e3fbf8]">
                                    <img
                                        v-if="item.product.image_url"
                                        :src="item.product.image_url"
                                        :alt="item.product.name"
                                        class="h-full w-full object-cover"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                    <i v-else class="fa-solid fa-spa text-[28px] text-brand"></i>
                                </span>
                                <span class="font-poppins text-[16px] font-semibold leading-tight text-[#333]">
                                    {{ item.product.name }}
                                </span>
                            </a>

                            <div class="font-montserrat text-[15px] font-semibold text-[#555]">
                                {{ formatCents(item.unit_price_cents) }}
                            </div>

                            <QuantityInput :item="item" />

                            <div class="font-poppins text-[16px] font-semibold text-[#333]">
                                {{ formatCents(item.total_cents) }}
                            </div>

                            <button
                                type="button"
                                class="grid h-[38px] w-[38px] place-items-center rounded-full text-[#999] transition hover:bg-[#f2f2f2] hover:text-brand"
                                aria-label="Remover item"
                                @click="removeItem(item)"
                            >
                                <i class="fa-solid fa-trash-can text-[15px]"></i>
                            </button>
                        </article>
                    </div>

                    <CartSummary :cart="cart" />
                </div>

                <div v-else class="mt-[34px] rounded-[4px] bg-white px-6 py-10 text-center shadow-[0_0_10px_rgba(0,0,0,0.12)]">
                    <p class="font-montserrat text-[17px] text-[#666]">Seu carrinho está vazio.</p>
                    <a href="/depilacao-feminina" class="mt-6 inline-flex h-[46px] items-center justify-center rounded-[3px] bg-brand px-6 font-poppins text-[15px] font-semibold text-white transition duration-200 hover:-translate-y-[2px] hover:brightness-105 hover:shadow-[0_10px_22px_rgba(41,216,219,0.25)] active:translate-y-0">
                        Ver pacotes
                    </a>
                </div>
            </div>
        </section>
    </SiteLayout>
</template>

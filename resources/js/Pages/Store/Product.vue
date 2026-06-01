<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SiteLayout from '../../Layouts/SiteLayout.vue';

const props = defineProps({
    product: { type: Object, required: true },
    breadcrumbs: { type: Array, default: () => [] },
    technicalSpecs: { type: Array, default: () => [] },
    structuredData: { type: Object, required: true },
});

const activeTab = ref('details');
const currentPrice = computed(() => props.product.sale_price_cents || props.product.price_cents || 0);
const regularPrice = computed(() => props.product.regular_price_cents || currentPrice.value);
const hasDiscount = computed(() => regularPrice.value > currentPrice.value && currentPrice.value > 0);
const discountPercent = computed(() => {
    if (!hasDiscount.value) {
        return null;
    }

    return Math.round(((regularPrice.value - currentPrice.value) / regularPrice.value) * 100);
});
const installments = computed(() => currentPrice.value >= 7800 ? 12 : Math.max(1, Math.floor(currentPrice.value / 3000)));
const installmentPrice = computed(() => Math.ceil(currentPrice.value / installments.value));
const jsonLd = computed(() => JSON.stringify(props.structuredData));
const quantity = ref(1);

function formatCents(cents, useGrouping = true) {
    const value = Number(cents || 0) / 100;

    return `R$ ${value.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        useGrouping,
    })}`;
}

function addToCart() {
    router.post('/carrinho/items', {
        product_id: props.product.id,
        quantity: quantity.value,
    });
}
</script>

<template>
    <Head :title="product.name">
        <meta name="description" :content="product.short_description" />
        <script type="application/ld+json">{{ jsonLd }}</script>
    </Head>

    <SiteLayout header-variant="store">
        <section class="bg-[#f7f7f7] px-5 pb-0 pt-[17px]">
            <div class="mx-auto max-w-[1140px]">
                <nav class="mb-[15px] flex flex-wrap items-center gap-x-[5px] gap-y-1 font-montserrat text-[15px] font-medium text-brand">
                    <template v-for="(crumb, index) in breadcrumbs" :key="crumb.slug">
                        <a v-if="crumb.href" :href="crumb.href" class="hover:underline">{{ crumb.name }}</a>
                        <span v-else class="text-brand">{{ crumb.name }}</span>
                        <span v-if="index < breadcrumbs.length - 1">/</span>
                    </template>
                </nav>

                <div class="grid rounded-[4px] bg-white p-[10px] shadow-[0_0_10px_rgba(0,0,0,0.08)] transition duration-300 hover:shadow-[0_12px_28px_rgba(0,0,0,0.10)] lg:grid-cols-[330px_1fr] lg:gap-[30px]">
                    <div class="group relative overflow-hidden rounded-[4px] bg-[#d9fbf7]">
                        <img
                            v-if="product.image_url"
                            :src="product.image_url"
                            :alt="product.image_alt"
                            class="h-[300px] w-full object-cover transition duration-500 ease-out group-hover:scale-[1.025] lg:h-[300px]"
                            loading="eager"
                            decoding="async"
                        >
                        <div v-else class="grid h-[300px] place-items-center">
                            <i class="fa-solid fa-spa text-[68px] text-brand"></i>
                        </div>
                        <div class="absolute bottom-[7px] left-1/2 flex -translate-x-1/2 gap-[10px]">
                            <span class="h-[6px] w-[6px] rounded-full bg-brand"></span>
                            <span class="h-[6px] w-[6px] rounded-full bg-[#b9d9d5]"></span>
                            <span class="h-[6px] w-[6px] rounded-full bg-[#b9d9d5]"></span>
                            <span class="h-[6px] w-[6px] rounded-full bg-[#b9d9d5]"></span>
                        </div>
                    </div>

                    <div class="px-1 py-[10px] lg:px-0">
                        <h1 class="font-poppins text-[22px] font-semibold leading-tight text-[#363636]">
                            {{ product.name }}
                        </h1>

                        <p v-if="product.short_description" class="mt-[18px] max-w-[770px] font-montserrat text-[16px] leading-[1.45] text-[#3b3b3b]">
                            {{ product.short_description }}
                        </p>

                        <div class="mt-[55px] font-poppins text-[#333]">
                            <p v-if="hasDiscount" class="text-[16px] leading-none text-[#8d8d8d] line-through">
                                {{ formatCents(regularPrice, true).replace(',00', '') }}
                            </p>
                            <div v-if="currentPrice > 0" class="mt-[8px] flex flex-wrap items-baseline gap-[10px]">
                                <strong class="text-[28px] font-medium leading-none">
                                    {{ formatCents(currentPrice, false).replace(',00', '') }}
                                </strong>
                                <span v-if="discountPercent" class="text-[16px] font-medium text-[#00b139]">
                                    {{ discountPercent }}% OFF
                                </span>
                            </div>
                            <p v-if="currentPrice > 0" class="mt-[8px] text-[16px] font-medium leading-tight text-[#00a537]">
                                em {{ installments }}x de {{ formatCents(installmentPrice) }} sem juros
                            </p>
                            <p v-else class="mt-[8px] text-[16px] font-semibold text-[#00a537]">
                                Fale com uma especialista para montar seu combo.
                            </p>
                        </div>

                        <form class="mt-[38px] flex flex-wrap items-center gap-[12px]" @submit.prevent="addToCart">
                            <input
                                v-model.number="quantity"
                                type="number"
                                name="quantity"
                                min="1"
                                class="h-[52px] w-[72px] rounded-[2px] border border-[#ddd] px-3 text-center font-poppins text-[16px] text-[#333] outline-none focus:border-brand"
                                aria-label="Quantidade"
                            >
                            <button
                                type="submit"
                                class="h-[52px] rounded-[3px] bg-brand px-[24px] font-poppins text-[18px] font-semibold text-white transition duration-200 hover:-translate-y-[2px] hover:brightness-105 hover:shadow-[0_10px_22px_rgba(41,216,219,0.25)] active:translate-y-0"
                            >
                                Adicionar ao carrinho
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-[#e8f7f7] px-5 py-[24px] lg:py-[24px]">
            <div class="mx-auto max-w-[1140px] rounded-[4px] bg-white px-[30px] pb-[30px] pt-[10px]">
                <div class="mx-auto grid max-w-[590px] grid-cols-2 font-poppins text-[16px] uppercase text-[#555]">
                    <button
                        type="button"
                        class="h-[46px] px-4 transition"
                        :class="activeTab === 'details' ? 'bg-[#c7c7c7] text-white' : 'bg-[#efefef]'"
                        @click="activeTab = 'details'"
                    >
                        Detalhesdo procedimento
                    </button>
                    <button
                        type="button"
                        class="h-[46px] px-4 transition"
                        :class="activeTab === 'specs' ? 'bg-[#c7c7c7] text-white' : 'bg-[#efefef]'"
                        @click="activeTab = 'specs'"
                    >
                        Especificações técnicas
                    </button>
                </div>

                <div v-if="activeTab === 'details'" class="mt-[20px] grid gap-[26px] lg:grid-cols-2 lg:gap-x-[50px]">
                    <div class="flex items-center gap-[20px]">
                        <span class="grid h-[80px] w-[80px] shrink-0 place-items-center rounded-full bg-[#fee8d2] text-[#e57255] transition duration-300 hover:scale-105">
                            <i class="fa-solid fa-hand-dots text-[36px]"></i>
                        </span>
                        <p class="font-montserrat text-[16px] leading-[1.45] text-[#363636]">
                            Para garantir a eficácia do tratamento, a área deve ser depilada com lâmina um dia antes, sem deixar pelos aparentes.
                        </p>
                    </div>
                    <div class="flex items-center gap-[20px]">
                        <span class="grid h-[80px] w-[80px] shrink-0 place-items-center rounded-full bg-brand text-white transition duration-300 hover:scale-105">
                            <i class="fa-solid fa-calendar-days text-[36px]"></i>
                        </span>
                        <p class="font-montserrat text-[16px] leading-[1.45] text-[#363636]">
                            O intervalo entre as sessões é de 30 dias, mas pode se alongar conforme a evolução do tratamento.
                        </p>
                    </div>
                    <div class="flex items-center gap-[20px]">
                        <span class="grid h-[80px] w-[80px] shrink-0 place-items-center rounded-full bg-[#7554a3] text-white transition duration-300 hover:scale-105">
                            <i class="fa-solid fa-wand-magic-sparkles text-[32px]"></i>
                        </span>
                        <p class="font-montserrat text-[16px] leading-[1.45] text-[#363636]">
                            Você pode se depilar entre as sessões com métodos que não arranquem o pelo pela raiz. Sugerimos apenas o uso de lâmina.
                        </p>
                    </div>
                    <div class="flex items-center gap-[20px]">
                        <span class="grid h-[80px] w-[80px] shrink-0 place-items-center rounded-full bg-[#cbf5f3] text-brand transition duration-300 hover:scale-105">
                            <i class="fa-solid fa-user-shield text-[34px]"></i>
                        </span>
                        <p class="font-montserrat text-[16px] leading-[1.45] text-[#363636]">
                            Para seu conforto e segurança, compareça às sessões com as áreas a serem depiladas higienizadas.
                        </p>
                    </div>
                </div>

                <div v-else class="mx-auto mt-[26px] max-w-[720px] overflow-hidden rounded-[4px] border border-[#ededed] font-montserrat text-[16px] text-[#444]">
                    <div v-for="spec in technicalSpecs" :key="spec.label" class="grid grid-cols-[190px_1fr] border-b border-[#ededed] last:border-0">
                        <div class="bg-[#f7f7f7] px-5 py-4 font-semibold">{{ spec.label }}</div>
                        <div class="px-5 py-4">{{ spec.value }}</div>
                    </div>
                </div>
            </div>
        </section>
    </SiteLayout>
</template>

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

// Product image gallery (carousel). Falls back to the single image_url, or to a
// placeholder when there is none.
const gallery = computed(() => {
    const imgs = Array.isArray(props.product.images) ? props.product.images.filter((i) => i && i.url) : [];
    if (imgs.length) return imgs;
    if (props.product.image_url) return [{ url: props.product.image_url, alt: props.product.image_alt }];
    return [];
});
const activeImage = ref(0);
function goImage(i) {
    const n = gallery.value.length;
    if (!n) return;
    activeImage.value = (i + n) % n;
}

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
        <section class="bg-[linear-gradient(180deg,#f8fbfb_0%,#eef7f7_100%)] px-5 pb-7 pt-[22px]">
            <div class="mx-auto max-w-[1140px]">
                <nav class="mb-[16px] flex flex-wrap items-center gap-x-[6px] gap-y-1 font-montserrat text-[14px] font-semibold text-brand">
                    <template v-for="(crumb, index) in breadcrumbs" :key="crumb.slug">
                        <a v-if="crumb.href" :href="crumb.href" class="hover:underline">{{ crumb.name }}</a>
                        <span v-else class="text-brand">{{ crumb.name }}</span>
                        <span v-if="index < breadcrumbs.length - 1">/</span>
                    </template>
                </nav>

                <div class="grid overflow-hidden rounded-[8px] bg-white p-3 shadow-[0_10px_30px_rgba(0,0,0,0.10)] ring-1 ring-black/[0.03] transition duration-300 hover:shadow-[0_16px_34px_rgba(0,0,0,0.12)] lg:grid-cols-[360px_1fr] lg:gap-[34px] lg:p-4">
                    <div class="lg:self-start">
                        <div class="group relative overflow-hidden rounded-[6px] bg-[#d9fbf7]">
                            <template v-if="gallery.length">
                                <img
                                    :src="gallery[activeImage].url"
                                    :alt="gallery[activeImage].alt"
                                    class="h-[310px] w-full object-cover transition duration-300 ease-out lg:h-[360px]"
                                    loading="eager"
                                    decoding="async"
                                >

                                <!-- Prev / next (only with more than one image) -->
                                <template v-if="gallery.length > 1">
                                    <button
                                        type="button"
                                        class="absolute left-2 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-white/85 text-brand shadow-md transition hover:bg-white"
                                        aria-label="Imagem anterior"
                                        @click="goImage(activeImage - 1)"
                                    >
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="absolute right-2 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-white/85 text-brand shadow-md transition hover:bg-white"
                                        aria-label="Próxima imagem"
                                        @click="goImage(activeImage + 1)"
                                    >
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </button>
                                </template>

                                <!-- Dots -->
                                <div v-if="gallery.length > 1" class="absolute bottom-[7px] left-1/2 flex -translate-x-1/2 gap-[10px]">
                                    <button
                                        v-for="(img, i) in gallery"
                                        :key="i"
                                        type="button"
                                        class="h-[7px] w-[7px] rounded-full transition"
                                        :class="i === activeImage ? 'bg-brand' : 'bg-[#b9d9d5]'"
                                        :aria-label="`Ver imagem ${i + 1}`"
                                        @click="goImage(i)"
                                    ></button>
                                </div>
                            </template>
                            <div v-else class="grid h-[310px] place-items-center lg:h-[360px]">
                                <i class="fa-solid fa-spa text-[68px] text-brand"></i>
                            </div>
                        </div>

                        <!-- Thumbnails -->
                        <div v-if="gallery.length > 1" class="mt-3 flex gap-2 overflow-x-auto [scrollbar-width:none]">
                            <button
                                v-for="(img, i) in gallery"
                                :key="i"
                                type="button"
                                class="h-[58px] w-[58px] shrink-0 overflow-hidden rounded-[5px] ring-2 transition"
                                :class="i === activeImage ? 'ring-brand' : 'ring-transparent hover:ring-[#b9d9d5]'"
                                @click="goImage(i)"
                            >
                                <img :src="img.url" :alt="img.alt" class="h-full w-full object-cover" loading="lazy">
                            </button>
                        </div>
                    </div>

                    <div class="px-1 py-4 lg:flex lg:flex-col lg:px-0 lg:py-5">
                        <h1 class="font-poppins text-[25px] font-extrabold leading-tight text-[#363636] lg:text-[31px]">
                            {{ product.name }}
                        </h1>

                        <p v-if="product.short_description" class="mt-[16px] max-w-[720px] font-montserrat text-[16px] leading-[1.65] text-[#555]">
                            {{ product.short_description }}
                        </p>

                        <div class="mt-8 rounded-[8px] bg-[#f7fbfb] p-5 font-poppins text-[#333] ring-1 ring-[#e3eeee] lg:mt-auto">
                            <p v-if="hasDiscount" class="text-[16px] leading-none text-[#8d8d8d] line-through">
                                {{ formatCents(regularPrice, true).replace(',00', '') }}
                            </p>
                            <div v-if="currentPrice > 0" class="mt-[8px] flex flex-wrap items-baseline gap-[10px]">
                                <strong class="text-[34px] font-semibold leading-none">
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

                        <form class="mt-5 flex flex-wrap items-center gap-[12px]" @submit.prevent="addToCart">
                            <input
                                v-model.number="quantity"
                                type="number"
                                name="quantity"
                                min="1"
                                class="h-[52px] w-[76px] rounded-[4px] border border-[#dce5e5] bg-white px-3 text-center font-poppins text-[16px] text-[#333] outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20"
                                aria-label="Quantidade"
                            >
                            <button
                                type="submit"
                                class="h-[52px] flex-1 rounded-[4px] bg-brand px-[24px] font-poppins text-[17px] font-semibold text-white transition duration-200 hover:-translate-y-[2px] hover:brightness-105 hover:shadow-[0_10px_22px_rgba(41,216,219,0.25)] active:translate-y-0 sm:flex-none"
                            >
                                Adicionar ao carrinho
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="product.description" class="bg-white px-5 pt-10 lg:pt-12">
            <div
                class="prose-product mx-auto max-w-[760px] font-montserrat text-[16px] leading-[1.7] text-[#444]"
                v-html="product.description"
            ></div>
        </section>

        <section class="bg-[#e8f7f7] px-5 py-8 lg:py-10">
            <div class="mx-auto max-w-[1140px] rounded-[8px] bg-white px-5 pb-8 pt-5 shadow-[0_8px_26px_rgba(0,0,0,0.08)] lg:px-[30px]">
                <div class="mx-auto grid max-w-[640px] grid-cols-2 overflow-hidden rounded-[6px] bg-[#f0f0f0] font-poppins text-[13px] uppercase text-[#555] sm:text-[15px]">
                    <button
                        type="button"
                        class="min-h-[48px] px-3 transition"
                        :class="activeTab === 'details' ? 'bg-brand text-white' : 'bg-[#f0f0f0] hover:bg-[#e9e9e9]'"
                        @click="activeTab = 'details'"
                    >
                        Detalhes do procedimento
                    </button>
                    <button
                        type="button"
                        class="min-h-[48px] px-3 transition"
                        :class="activeTab === 'specs' ? 'bg-brand text-white' : 'bg-[#f0f0f0] hover:bg-[#e9e9e9]'"
                        @click="activeTab = 'specs'"
                    >
                        Especificações técnicas
                    </button>
                </div>

                <div v-if="activeTab === 'details'" class="mt-7 grid gap-6 lg:grid-cols-2 lg:gap-x-[50px]">
                    <div class="flex items-center gap-[20px] rounded-[8px] bg-[#fbfbfb] p-4">
                        <span class="grid h-[80px] w-[80px] shrink-0 place-items-center rounded-full bg-[#fee8d2] text-[#e57255] transition duration-300 hover:scale-105">
                            <i class="fa-solid fa-hand-dots text-[36px]"></i>
                        </span>
                        <p class="font-montserrat text-[16px] leading-[1.45] text-[#363636]">
                            Para garantir a eficácia do tratamento, a área deve ser depilada com lâmina um dia antes, sem deixar pelos aparentes.
                        </p>
                    </div>
                    <div class="flex items-center gap-[20px] rounded-[8px] bg-[#fbfbfb] p-4">
                        <span class="grid h-[80px] w-[80px] shrink-0 place-items-center rounded-full bg-brand text-white transition duration-300 hover:scale-105">
                            <i class="fa-solid fa-calendar-days text-[36px]"></i>
                        </span>
                        <p class="font-montserrat text-[16px] leading-[1.45] text-[#363636]">
                            O intervalo entre as sessões é de 30 dias, mas pode se alongar conforme a evolução do tratamento.
                        </p>
                    </div>
                    <div class="flex items-center gap-[20px] rounded-[8px] bg-[#fbfbfb] p-4">
                        <span class="grid h-[80px] w-[80px] shrink-0 place-items-center rounded-full bg-[#7554a3] text-white transition duration-300 hover:scale-105">
                            <i class="fa-solid fa-wand-magic-sparkles text-[32px]"></i>
                        </span>
                        <p class="font-montserrat text-[16px] leading-[1.45] text-[#363636]">
                            Você pode se depilar entre as sessões com métodos que não arranquem o pelo pela raiz. Sugerimos apenas o uso de lâmina.
                        </p>
                    </div>
                    <div class="flex items-center gap-[20px] rounded-[8px] bg-[#fbfbfb] p-4">
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

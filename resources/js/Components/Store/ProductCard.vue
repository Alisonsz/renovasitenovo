<script setup>
import { computed } from 'vue';

const props = defineProps({
    product: { type: Object, required: true },
    buttonLabel: { type: String, default: 'Ver oferta' },
});

const currentPrice = computed(() => props.product.sale_price_cents || props.product.price_cents || 0);
const regularPrice = computed(() => props.product.regular_price_cents || currentPrice.value);
const hasDiscount = computed(() => regularPrice.value > currentPrice.value && currentPrice.value > 0);
const discountPercent = computed(() => {
    if (!hasDiscount.value) {
        return null;
    }

    return Math.round(((regularPrice.value - currentPrice.value) / regularPrice.value) * 100);
});
const installmentPrice = computed(() => Math.ceil(currentPrice.value / 12));

function formatCents(cents) {
    const value = Number(cents || 0) / 100;

    return `R$ ${value.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        useGrouping: false,
    })}`;
}
</script>

<template>
    <article class="flex min-h-[502px] w-full max-w-[270px] flex-col overflow-hidden rounded-[8px] bg-white text-center shadow-[0_0_10px_rgba(0,0,0,0.27)]">
        <a :href="`/produto/${product.slug}`" class="mx-[15px] mt-[15px] block h-[240px] overflow-hidden rounded-[4px] bg-[#f3fbfb]">
            <img
                v-if="product.image_url"
                :src="product.image_url"
                :alt="product.name"
                class="h-full w-full object-cover"
                loading="lazy"
                decoding="async"
            >
            <span v-else class="grid h-full place-items-center">
                <i class="fa-solid fa-spa text-[58px] text-brand"></i>
            </span>
        </a>

        <div class="flex flex-1 flex-col px-[18px] pt-[24px]">
            <h2 class="min-h-[46px] font-poppins text-[18px] font-semibold leading-[1.3] text-[#333]">
                {{ product.name }}
            </h2>

            <div class="mt-[18px] min-h-[98px] font-poppins text-[#333]">
                <p v-if="hasDiscount" class="text-[16px] leading-none text-[#9a9a9a] line-through">
                    {{ formatCents(regularPrice) }}
                </p>
                <div v-if="currentPrice > 0" class="mt-[7px] flex items-baseline justify-center gap-[7px]">
                    <strong class="whitespace-nowrap text-[30px] font-medium leading-none tracking-normal">
                        {{ formatCents(currentPrice) }}
                    </strong>
                    <span v-if="discountPercent" class="text-[13px] font-semibold leading-none text-[#56b146]">
                        {{ discountPercent }}% OFF
                    </span>
                </div>
                <p v-if="currentPrice > 0" class="mt-[11px] text-[13px] font-medium leading-tight text-[#3da83c]">
                    em 12x de {{ formatCents(installmentPrice) }} sem juros
                </p>
                <p v-else class="mt-[18px] text-[15px] font-semibold text-[#3da83c]">
                    Monte seu combo com uma especialista
                </p>
            </div>
        </div>

        <a
            :href="`/produto/${product.slug}`"
            class="mt-auto flex h-[47px] items-center justify-center bg-brand px-4 font-poppins text-[15px] font-semibold text-white transition hover:brightness-105"
        >
            {{ buttonLabel }}
        </a>
    </article>
</template>

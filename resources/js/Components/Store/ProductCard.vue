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

function priceParts(cents) {
    const [amount, centsPart] = formatCents(cents).split(',');

    return {
        amount,
        cents: centsPart || '00',
    };
}
</script>

<template>
    <article class="group flex min-h-[360px] w-full flex-col overflow-hidden rounded-[8px] bg-white text-left shadow-[0_0_10px_rgba(0,0,0,0.24)] transition duration-300 ease-out hover:-translate-y-[4px] hover:shadow-[0_12px_28px_rgba(0,0,0,0.18)] lg:min-h-[502px] lg:max-w-[270px] lg:text-center">
        <a :href="`/produto/${product.slug}`" class="mx-auto mt-[16px] block aspect-square w-[calc(100%_-_16px)] overflow-hidden rounded-full bg-[#f3fbfb] lg:mx-[15px] lg:mt-[15px] lg:aspect-auto lg:h-[240px] lg:w-auto lg:rounded-[4px]">
            <img
                v-if="product.image_url"
                :src="product.image_url"
                :alt="product.name"
                class="h-full w-full object-cover transition duration-500 ease-out group-hover:scale-[1.035]"
                loading="lazy"
                decoding="async"
            >
            <span v-else class="grid h-full place-items-center">
                <i class="fa-solid fa-spa text-[58px] text-brand"></i>
            </span>
        </a>

        <div class="flex flex-1 flex-col px-[10px] pt-[17px] lg:px-[18px] lg:pt-[24px]">
            <h2 class="line-clamp-3 min-h-[54px] font-poppins text-[14px] font-semibold leading-[1.18] text-[#202020] sm:text-[15px] lg:line-clamp-none lg:min-h-[46px] lg:text-[18px] lg:leading-[1.3] lg:text-[#333]">
                {{ product.name }}
            </h2>

            <div class="mt-[13px] flex min-h-[78px] flex-col justify-end pb-[18px] font-poppins text-[#111] lg:mt-[18px] lg:block lg:min-h-[98px] lg:pb-0 lg:text-[#333]">
                <p v-if="hasDiscount" class="text-[12px] leading-none text-[#9a9a9a] line-through lg:text-[16px]">
                    {{ formatCents(regularPrice) }}
                </p>
                <div v-if="currentPrice > 0" class="mt-[6px] flex min-w-0 items-baseline justify-start gap-[3px] whitespace-nowrap lg:mt-[7px] lg:justify-center lg:gap-[7px]">
                    <strong class="min-w-0 font-poppins text-[19px] font-normal leading-none tracking-normal text-[#111] lg:text-[30px] lg:font-medium lg:text-[#333]">
                        {{ priceParts(currentPrice).amount }}<span class="text-[11px] font-normal lg:text-[17px]">,{{ priceParts(currentPrice).cents }}</span>
                    </strong>
                    <span v-if="discountPercent" class="shrink-0 text-[12px] font-normal leading-none text-[#16b53c] lg:text-[13px] lg:font-semibold lg:text-[#56b146]">
                        {{ discountPercent }}% OFF
                    </span>
                </div>
                <p v-if="currentPrice > 0" class="mt-[7px] whitespace-nowrap text-[10.5px] font-normal leading-tight text-[#009b2f] lg:mt-[11px] lg:whitespace-normal lg:text-[13px] lg:font-medium lg:text-[#3da83c]">
                    <span class="text-[#111] lg:text-[#3da83c]">em</span> 12x de {{ formatCents(installmentPrice) }} sem juros
                </p>
                <p v-else class="mt-[18px] text-[13px] font-semibold text-[#3da83c] lg:text-[15px]">
                    Monte seu combo com uma especialista
                </p>
            </div>
        </div>

        <a
            :href="`/produto/${product.slug}`"
            class="mt-auto flex h-[56px] items-center justify-center bg-brand px-3 text-center font-poppins text-[15px] font-semibold text-white transition duration-200 hover:brightness-105 active:translate-y-px lg:h-[47px] lg:px-4"
        >
            {{ buttonLabel }}
        </a>
    </article>
</template>

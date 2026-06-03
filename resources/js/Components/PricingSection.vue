<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import PricingModal from './PricingModal.vue';
import { PRICING } from '../data/site.js';
import { useDragScroll } from '../Composables/useDragScroll.js';

const active = ref(null);
// Loja desativada por padrão: enquanto não houver flag explícito, tudo aponta
// para o WhatsApp (modal de preços inclusive). Defina VITE_ATIVAR_LOJA=true
// para reativar a loja.
const storeFlag = String(import.meta.env.VITE_ATIVAR_LOJA ?? import.meta.env.VITE_STORE_ENABLED ?? 'false').toLowerCase();
const storeEnabled = ['true', '1', 'on', 'yes', 'sim'].includes(storeFlag);
const track = ref(null);

// On desktop (lg) the cards are centered and don't scroll — keep the exact
// layout (one set). On smaller screens the row scrolls, so we triple the items
// for a seamless infinite loop. 1024px = lg breakpoint.
const isDesktop = ref(false);
let mql = null;

const loopPricing = computed(() =>
    isDesktop.value ? PRICING : Array.from({ length: 3 }, () => PRICING).flat()
);

const { recenter } = useDragScroll(track, {
    autoplayMs: 4200,
    enabled: () => !isDesktop.value,
});

function onBreakpoint(e) {
    isDesktop.value = e.matches;
    // Item count changes between layouts → re-center the loop afterwards.
    requestAnimationFrame(recenter);
}

onMounted(() => {
    mql = window.matchMedia('(min-width: 1024px)');
    isDesktop.value = mql.matches;
    mql.addEventListener('change', onBreakpoint);
});

onBeforeUnmount(() => {
    if (mql) mql.removeEventListener('change', onBreakpoint);
});
</script>

<template>
    <section class="relative mb-[-140px] overflow-x-clip overflow-y-visible bg-[linear-gradient(180deg,#FFFFFF_0%,#DBE8E9_100%)] px-5 pt-14 pb-[92px] lg:mb-[-166px] lg:pt-[120px] lg:pb-[130px]">
        <div class="mx-auto max-w-[1140px]">
            <div class="text-center">
                <h2 class="text-[28px] font-extrabold text-heading lg:text-[39px]">Conheça nossos preços</h2>
                <p class="mt-2 text-[18px] font-normal text-muted">Tem pacote, tem avulsa, tem pra você!</p>
            </div>

            <div class="relative mt-6 overflow-visible lg:mt-12">
                <div
                    ref="track"
                    class="relative z-20 -mx-5 cursor-grab overflow-x-auto overflow-y-hidden overscroll-x-contain scroll-smooth px-14 py-7 [scrollbar-width:none] [touch-action:pan-x_pan-y] active:cursor-grabbing lg:mx-0 lg:overflow-visible lg:px-0 lg:py-4"
                >
                    <ul class="flex gap-5 select-none lg:justify-center">
                        <li
                            v-for="(card, i) in loopPricing"
                            :key="i + '-' + card.popup"
                            data-carousel-item
                            class="group flex w-[270px] shrink-0 flex-col overflow-hidden rounded-[12px] bg-white shadow-[0_6px_18px_rgba(0,0,0,0.22)] transition duration-300 ease-out hover:-translate-y-[4px] hover:shadow-[0_14px_28px_rgba(0,0,0,0.18)] sm:w-[calc(50%-10px)] lg:w-[325px] lg:rounded-[8px]"
                        >
                            <div class="flex flex-col items-center px-0 pt-[35px]">
                                <img
                                    :src="card.image"
                                    :alt="card.title"
                                    class="h-[150px] w-[150px] rounded-full object-cover transition duration-300 ease-out group-hover:scale-[1.035]"
                                    draggable="false"
                                    @dragstart.prevent
                                />
                                <h3 class="mt-4 px-5 text-center text-[22px] font-extrabold leading-tight text-heading">{{ card.title }}</h3>
                                <p class="mt-3 min-h-[88px] px-5 pb-3 text-center text-[15px] leading-relaxed text-muted lg:px-[30px]">{{ card.text }}</p>
                            </div>
                            <button
                                class="mt-auto h-[57px] w-full rounded-b-[12px] bg-brand font-poppins text-[15px] font-semibold leading-[15px] text-white transition duration-200 hover:brightness-105 active:translate-y-px lg:h-11 lg:rounded-b-[8px] lg:leading-normal"
                                @click="active = card"
                            >
                                {{ card.cta }}
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <PricingModal :open="!!active" :card="active" :store-enabled="storeEnabled" @close="active = null" />
    </section>
</template>

<style scoped>
div::-webkit-scrollbar {
    display: none;
}
</style>

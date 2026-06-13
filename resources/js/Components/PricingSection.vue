<script setup>
import { ref } from 'vue';
import PricingModal from './PricingModal.vue';
import { PRICING } from '../data/site.js';

const active = ref(null);
const pricingTrack = ref(null);
// Loja desativada por padrão: enquanto não houver flag explícito, tudo aponta
// para o WhatsApp (modal de preços inclusive). Defina VITE_ATIVAR_LOJA=true
// para reativar a loja.
const storeFlag = String(import.meta.env.VITE_ATIVAR_LOJA ?? import.meta.env.VITE_STORE_ENABLED ?? 'false').toLowerCase();
const storeEnabled = ['true', '1', 'on', 'yes', 'sim'].includes(storeFlag);

// Finite carousel (NOT infinite, unlike the other rows): just the 3 options,
// one card per view on mobile via native scroll-snap; three centered on desktop.
function scrollPricing(direction) {
    const el = pricingTrack.value;
    if (!el) return;

    el.scrollBy({
        left: direction * el.clientWidth,
        behavior: 'smooth',
    });
}
</script>

<template>
    <!-- overflow-clip (e NÃO overflow-x-clip + overflow-y-visible): o mix recorte+visível
         faz o WebKit/Safari 16+ tratar a seção como scroll container vertical aninhado,
         que captura o toque e trava a rolagem em alguns iPhones. clip nos dois eixos
         recorta igual mas não vira container de scroll. -->
    <section id="precos" class="relative mb-[-140px] scroll-mt-[70px] overflow-clip bg-[linear-gradient(180deg,#FFFFFF_0%,#DBE8E9_100%)] px-5 pt-14 pb-[92px] lg:mb-[-166px] lg:pt-[120px] lg:pb-[130px]">
        <div class="mx-auto max-w-[1140px]">
            <div class="text-center">
                <h2 class="text-[28px] font-extrabold text-heading lg:text-[39px]">Conheça nossos preços</h2>
                <p class="mt-2 text-[18px] font-normal text-muted">Tem pacote, tem avulsa, tem pra você!</p>
            </div>

            <div class="relative mt-6 overflow-visible lg:mt-12">
                <button
                    type="button"
                    class="absolute -left-2 top-1/2 z-30 flex h-10 w-10 -translate-y-1/2 items-center justify-center text-brand lg:hidden"
                    aria-label="Preço anterior"
                    @click="scrollPricing(-1)"
                >
                    <i class="fa-solid fa-play rotate-180 text-[19px]"></i>
                </button>

                <div
                    ref="pricingTrack"
                    class="relative z-20 flex snap-x snap-mandatory overflow-x-auto overscroll-x-contain scroll-smooth py-7 [scrollbar-width:none] [touch-action:pan-x_pan-y] lg:snap-none lg:justify-center lg:gap-5 lg:overflow-visible lg:py-4"
                >
                    <div
                        v-for="card in PRICING"
                        :key="card.popup"
                        class="flex w-full shrink-0 snap-center justify-center px-2 lg:w-[325px] lg:px-0"
                    >
                        <div class="group flex w-[275px] max-w-[calc(100vw-48px)] flex-col overflow-hidden rounded-[12px] bg-white shadow-[0_6px_18px_rgba(0,0,0,0.22)] transition duration-300 ease-out hover:-translate-y-[4px] hover:shadow-[0_14px_28px_rgba(0,0,0,0.18)] lg:w-full lg:max-w-none lg:rounded-[8px]">
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
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    class="absolute -right-2 top-1/2 z-30 flex h-10 w-10 -translate-y-1/2 items-center justify-center text-brand lg:hidden"
                    aria-label="Próximo preço"
                    @click="scrollPricing(1)"
                >
                    <i class="fa-solid fa-play text-[19px]"></i>
                </button>
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

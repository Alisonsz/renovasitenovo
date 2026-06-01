<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import PricingModal from './PricingModal.vue';
import { PRICING } from '../data/site.js';

const active = ref(null);
const track = ref(null);
let autoScrollTimer = null;
let resumeTimer = null;

function stopAutoScroll() {
    if (autoScrollTimer) clearInterval(autoScrollTimer);
    autoScrollTimer = null;
}

function startAutoScroll() {
    stopAutoScroll();
    autoScrollTimer = setInterval(() => {
        const el = track.value;
        if (!el || el.scrollWidth <= el.clientWidth) return;

        const next = el.scrollLeft + el.clientWidth * 0.82;
        el.scrollTo({
            left: next >= el.scrollWidth - el.clientWidth - 8 ? 0 : next,
            behavior: 'smooth',
        });
    }, 4200);
}

function pauseAutoScroll() {
    stopAutoScroll();
    if (resumeTimer) clearTimeout(resumeTimer);
    resumeTimer = setTimeout(startAutoScroll, 7000);
}

onMounted(startAutoScroll);

onBeforeUnmount(() => {
    stopAutoScroll();
    if (resumeTimer) clearTimeout(resumeTimer);
});
</script>

<template>
    <section class="bg-[linear-gradient(180deg,#FFFFFF_0%,#DBE8E9_100%)] px-5 py-14 lg:py-[120px]">
        <div class="mx-auto max-w-[1140px]">
            <div class="text-center">
                <h2 class="text-[28px] font-extrabold text-heading lg:text-[39px]">Conheça nossos preços</h2>
                <p class="mt-2 text-[18px] font-semibold text-brand-dark">Tem pacote, tem avulsa, tem pra você!</p>
            </div>

            <div class="relative mt-6 overflow-visible lg:mt-12">
                <ul
                    ref="track"
                    class="-mx-5 flex snap-x snap-mandatory gap-5 overflow-x-auto scroll-smooth px-14 py-7 [scrollbar-width:none] lg:mx-0 lg:justify-center lg:overflow-visible lg:px-0 lg:py-4"
                    @pointerdown="pauseAutoScroll"
                    @touchstart.passive="pauseAutoScroll"
                    @wheel.passive="pauseAutoScroll"
                >
                    <li
                        v-for="card in PRICING"
                        :key="card.popup"
                        class="group flex w-[270px] shrink-0 snap-center flex-col overflow-hidden rounded-[12px] bg-white shadow-[0_6px_18px_rgba(0,0,0,0.22)] transition duration-300 ease-out hover:-translate-y-[4px] hover:shadow-[0_14px_28px_rgba(0,0,0,0.18)] sm:w-[calc(50%-10px)] lg:w-[325px] lg:rounded-[8px]"
                    >
                        <div class="flex flex-col items-center px-0 pt-[35px]">
                            <img :src="card.image" :alt="card.title" class="h-[150px] w-[150px] rounded-full object-cover transition duration-300 ease-out group-hover:scale-[1.035]" />
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

        <PricingModal :open="!!active" :title="active?.title || ''" @close="active = null" />
    </section>
</template>

<style scoped>
ul::-webkit-scrollbar {
    display: none;
}
</style>

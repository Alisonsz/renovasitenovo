<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { FAQ, WHATSAPP } from '../data/site.js';

const track = ref(null);

let autoScrollTimer = null;
let resumeTimer = null;
let currentSlide = 0;

function stopAutoScroll() {
    clearInterval(autoScrollTimer);
    autoScrollTimer = null;
}

function startAutoScroll() {
    stopAutoScroll();
    autoScrollTimer = setInterval(() => {
        const el = track.value;
        if (!el || el.scrollWidth <= el.clientWidth) return;

        const cards = Array.from(el.children);
        currentSlide = currentSlide >= cards.length - 1 ? 0 : currentSlide + 1;
        scrollToSlide(currentSlide);
    }, 4200);
}

function getSlideLeft(card) {
    const el = track.value;
    if (!el) return 0;

    const paddingLeft = parseFloat(window.getComputedStyle(el).paddingLeft) || 0;
    return Math.min(card.offsetLeft - el.offsetLeft - paddingLeft, el.scrollWidth - el.clientWidth);
}

function scrollToSlide(index) {
    const el = track.value;
    const cards = el ? Array.from(el.children) : [];
    if (!el || !cards.length) return;

    currentSlide = index % cards.length;
    el.scrollTo({
        left: Math.max(0, getSlideLeft(cards[currentSlide])),
        behavior: 'smooth',
    });
}

function syncCurrentSlide() {
    const el = track.value;
    const cards = el ? Array.from(el.children) : [];
    if (!el || !cards.length) return;

    currentSlide = cards.reduce((closestIndex, card, index) => {
        const closestDistance = Math.abs(el.scrollLeft - getSlideLeft(cards[closestIndex]));
        const distance = Math.abs(el.scrollLeft - getSlideLeft(card));
        return distance < closestDistance ? index : closestIndex;
    }, 0);
}

function pauseAutoScroll() {
    syncCurrentSlide();
    stopAutoScroll();
    clearTimeout(resumeTimer);
    resumeTimer = setTimeout(startAutoScroll, 7000);
}

onMounted(startAutoScroll);
onBeforeUnmount(() => {
    stopAutoScroll();
    clearTimeout(resumeTimer);
});
</script>

<template>
    <section class="px-5 py-14 lg:py-20">
        <div class="mx-auto max-w-[1100px]">
            <h2 class="text-center text-[28px] font-extrabold text-heading lg:text-[39px]">Dúvidas frequentes</h2>

            <div class="relative mt-6 overflow-visible lg:mt-10">
                <ul
                    ref="track"
                    class="flex snap-x snap-mandatory gap-5 overflow-x-auto scroll-smooth px-3 py-5 [scrollbar-width:none] lg:grid lg:grid-cols-4 lg:overflow-visible lg:px-0"
                    @pointerdown="pauseAutoScroll"
                    @scroll.passive="syncCurrentSlide"
                    @touchstart.passive="pauseAutoScroll"
                    @wheel.passive="pauseAutoScroll"
                >
                    <li
                        v-for="item in FAQ"
                        :key="item.q"
                        class="flex w-[300px] shrink-0 snap-center flex-col rounded-[8px] bg-white px-[5px] py-[25px] text-center shadow-[0_5px_18px_rgba(0,0,0,0.20)] lg:w-full"
                    >
                        <h3 class="text-[18px] font-bold leading-tight text-brand-dark">{{ item.q }}</h3>
                        <p class="mt-[15px] px-3 text-[16px] leading-relaxed text-muted">{{ item.a }}</p>
                    </li>
                </ul>
            </div>

            <div class="mt-12 text-center">
                <h3 class="text-[22px] font-semibold leading-tight text-heading-soft lg:text-[29px]">
                    Não encontrou resposta para<br />sua dúvida aqui?
                </h3>
                <p class="mt-3 text-[16px] text-ink">Tire aqui todas as suas dúvidas</p>
                <a
                    :href="WHATSAPP.especialista"
                    target="_blank"
                    rel="noopener"
                    class="mt-5 inline-block rounded-[3px] bg-brand px-6 py-2.5 font-poppins text-[14px] font-semibold uppercase tracking-wide text-white transition hover:brightness-105"
                >
                    Falar com uma especialista
                </a>
            </div>
        </div>
    </section>
</template>

<style scoped>
ul::-webkit-scrollbar {
    display: none;
}
</style>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { FAQ, WHATSAPP } from '../data/site.js';
import { useDragScroll } from '../Composables/useDragScroll.js';

const track = ref(null);

// On desktop (lg) the cards become a static 4-column grid (no scroll) — keep that
// exact layout (one set). On smaller screens the row scrolls, so we triple the
// items for a seamless infinite loop. 1024px = Tailwind's lg breakpoint.
const isDesktop = ref(false);
let mql = null;

const loopFaq = computed(() =>
    isDesktop.value ? FAQ : Array.from({ length: 3 }, () => FAQ).flat()
);

const { recenter } = useDragScroll(track, {
    autoplayMs: 4200,
    enabled: () => !isDesktop.value,
});

function onBreakpoint(e) {
    isDesktop.value = e.matches;
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
    <section class="overflow-x-clip px-5 py-14 lg:py-20">
        <div class="mx-auto max-w-[1100px]">
            <h2 class="text-center text-[28px] font-extrabold text-heading lg:text-[39px]">Dúvidas frequentes</h2>

            <div class="relative mt-6 overflow-visible lg:mt-10">
                <div
                    ref="track"
                    class="cursor-grab overflow-x-auto overflow-y-hidden scroll-smooth py-5 [scrollbar-width:none] [touch-action:pan-x_pan-y] active:cursor-grabbing lg:overflow-visible"
                >
                    <ul class="flex select-none lg:grid lg:grid-cols-4 lg:gap-5">
                        <li
                            v-for="(item, i) in loopFaq"
                            :key="i + '-' + item.q"
                            data-carousel-item
                            class="flex w-full shrink-0 justify-center px-2 lg:w-full lg:px-0"
                        >
                            <div
                                class="flex w-[300px] max-w-[calc(100vw-32px)] flex-col rounded-[8px] bg-white px-[5px] py-[25px] text-center shadow-[0_5px_18px_rgba(0,0,0,0.20)] lg:w-full lg:max-w-none"
                            >
                                <h3 class="text-[18px] font-bold leading-tight text-brand-dark">{{ item.q }}</h3>
                                <p class="mt-[15px] px-3 text-[16px] leading-relaxed text-muted">{{ item.a }}</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div id="contato" class="mt-12 scroll-mt-[80px] text-center">
                <h3 class="text-[22px] font-semibold leading-tight text-heading-soft lg:text-[29px]">
                    Não encontrou resposta para<br />sua dúvida aqui?
                </h3>
                <p class="mt-3 text-[16px] text-ink">Tire aqui todas as suas dúvidas</p>
                <a
                    :href="WHATSAPP.especialista"
                    target="_blank"
                    rel="noopener"
                    class="mt-5 inline-block rounded-[3px] bg-brand px-6 py-3 font-poppins text-[14px] font-semibold uppercase tracking-wide text-white transition hover:brightness-105"
                >
                    Falar com uma especialista
                </a>
            </div>
        </div>
    </section>
</template>

<style scoped>
div::-webkit-scrollbar {
    display: none;
}
</style>

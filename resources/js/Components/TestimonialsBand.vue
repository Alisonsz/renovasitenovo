<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { TRUSTINDEX_WIDGET_ID } from '../data/site.js';

const frame = ref(null);
const height = ref(430);
let timer = null;

// O loader do TrustIndex insere o widget logo apos a sua propria tag <script>,
// usando document.currentScript — que e null quando o script e injetado via JS
// (SPA). Por isso usamos um iframe com srcdoc: dentro dele o <script> e
// "parser-inserted" e o widget renderiza normalmente, igual ao site original.
const srcdoc = computed(() => {
    if (!TRUSTINDEX_WIDGET_ID) return '';
    return [
        '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8">',
        '<meta name="viewport" content="width=device-width, initial-scale=1">',
        '<base target="_blank">',
        '<style>',
        'html,body{margin:0;padding:0;background:transparent;overflow:visible}',
        '.ti-widget,.ti-widget-container,.ti-reviews-container{overflow:visible!important}',
        '.ti-reviews-container-wrapper{overflow-x:auto!important;overflow-y:visible!important;margin-left:0!important;margin-right:0!important;padding:12px!important;box-sizing:border-box!important;scrollbar-width:none!important;cursor:grab!important;touch-action:pan-y!important}',
        '.ti-reviews-container-wrapper::-webkit-scrollbar{display:none}',
        '</style>',
        '</head><body>',
        `<script defer async src="https://cdn.trustindex.io/loader.js?${TRUSTINDEX_WIDGET_ID}"><\/script>`,
        '<script>',
        '(() => {',
        '  function enhanceTrustCarousel() {',
        '    const wrapper = document.querySelector(".ti-reviews-container-wrapper");',
        '    if (!wrapper || wrapper.dataset.renovaDrag === "true") return;',
        '    wrapper.dataset.renovaDrag = "true";',
        '    let dragging = false;',
        '    let startX = 0;',
        '    let startLeft = 0;',
        '    wrapper.addEventListener("pointerdown", (event) => {',
        '      dragging = true;',
        '      startX = event.clientX;',
        '      startLeft = wrapper.scrollLeft;',
        '      wrapper.setPointerCapture?.(event.pointerId);',
        '      wrapper.style.cursor = "grabbing";',
        '    });',
        '    wrapper.addEventListener("pointermove", (event) => {',
        '      if (!dragging) return;',
        '      if (event.cancelable) event.preventDefault();',
        '      wrapper.scrollLeft = startLeft - (event.clientX - startX);',
        '    });',
        '    const stopDrag = () => {',
        '      dragging = false;',
        '      wrapper.style.cursor = "grab";',
        '    };',
        '    wrapper.addEventListener("pointerup", stopDrag);',
        '    wrapper.addEventListener("pointercancel", stopDrag);',
        '    wrapper.addEventListener("pointerleave", stopDrag);',
        '  }',
        '  const timer = setInterval(enhanceTrustCarousel, 300);',
        '  window.addEventListener("load", enhanceTrustCarousel);',
        '  setTimeout(() => clearInterval(timer), 10000);',
        '})();',
        '<\/script>',
        '</body></html>',
    ].join('');
});

function resize() {
    try {
        const doc = frame.value?.contentDocument;
        const h = doc?.body?.scrollHeight;
        if (h && h > 80) height.value = h;
    } catch (e) {
        /* ignore */
    }
}

onMounted(() => {
    if (!TRUSTINDEX_WIDGET_ID) return;
    timer = setInterval(resize, 500);
});
onBeforeUnmount(() => {
    if (timer) clearInterval(timer);
});
</script>

<template>
    <section class="relative flex min-h-[520px] items-start overflow-hidden bg-white px-5 pt-[190px] pb-16 lg:min-h-[760px] lg:pt-[260px] lg:pb-20">
        <!-- Aspas decorativas em par aberto/fechado. -->
        <img
            src="/images/aspas.png"
            alt=""
            aria-hidden="true"
            class="pointer-events-none absolute left-[-12px] top-0 w-[330px] rotate-180 select-none opacity-20 sm:w-[430px] lg:left-[4%] lg:w-[560px]"
        />

        <!-- Avaliacoes do Google via TrustIndex (mesmo widget do site original) -->
        <div class="relative z-10 -mx-3 w-[calc(100%+24px)] max-w-none lg:mx-auto lg:w-full lg:max-w-[1100px]">
            <img
                src="/images/aspas.png"
                alt=""
                aria-hidden="true"
                class="pointer-events-none absolute bottom-0 right-[-34px] z-20 w-[88px] select-none opacity-[0.14] sm:w-[116px] lg:right-[-70px] lg:w-[170px]"
            />
            <iframe
                v-if="TRUSTINDEX_WIDGET_ID"
                ref="frame"
                :srcdoc="srcdoc"
                :style="{ height: `min(${height}px, var(--reviews-height))` }"
                class="relative z-10 w-full border-0"
                scrolling="no"
                loading="lazy"
                title="Avaliações do Google - Renova Laser"
            ></iframe>
        </div>
    </section>
</template>

<style scoped>
section {
    --reviews-height: 430px;
}

@media (min-width: 1024px) {
    section {
        --reviews-height: 520px;
    }
}
</style>

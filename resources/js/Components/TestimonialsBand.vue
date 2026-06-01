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
        '<style>html,body{margin:0;padding:0;background:transparent;overflow:hidden}</style>',
        '</head><body>',
        `<script defer async src="https://cdn.trustindex.io/loader.js?${TRUSTINDEX_WIDGET_ID}"><\/script>`,
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
    <section class="relative flex min-h-[300px] items-center overflow-hidden bg-white px-5 py-4 lg:min-h-[650px] lg:pb-14 lg:pt-[90px]">
        <!-- Aspas decorativas iguais ao site antigo: menores e em par aberto/fechado. -->
        <img
            src="/images/aspas.png"
            alt=""
            aria-hidden="true"
            class="pointer-events-none absolute left-[5%] top-3 w-[90px] rotate-180 select-none opacity-20 sm:w-[120px] lg:left-[7%] lg:top-0 lg:w-[210px]"
        />
        <img
            src="/images/aspas.png"
            alt=""
            aria-hidden="true"
            class="pointer-events-none absolute bottom-4 right-[5%] w-[90px] select-none opacity-20 sm:w-[120px] lg:bottom-10 lg:right-[7%] lg:w-[210px]"
        />

        <!-- Avaliacoes do Google via TrustIndex (mesmo widget do site original) -->
        <div class="relative z-10 mx-auto w-full max-w-[1100px]">
            <iframe
                v-if="TRUSTINDEX_WIDGET_ID"
                ref="frame"
                :srcdoc="srcdoc"
                :style="{ height: `min(${height}px, var(--reviews-height))` }"
                class="w-full border-0"
                scrolling="no"
                loading="lazy"
                title="Avaliações do Google - Renova Laser"
            ></iframe>
        </div>
    </section>
</template>

<style scoped>
section {
    --reviews-height: 210px;
}

@media (min-width: 1024px) {
    section {
        --reviews-height: 430px;
    }
}
</style>

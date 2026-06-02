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
        'html,body{margin:0;background:transparent}',
        'body{box-sizing:border-box;padding:16px 18px}',
        '</style>',
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
    <section class="relative flex min-h-[430px] items-start overflow-visible bg-white px-0 pt-[70px] pb-14 sm:px-5 lg:min-h-[560px] lg:pt-[120px] lg:pb-20">
        <div class="relative z-10 mx-auto w-full max-w-[1120px] overflow-visible">
            <iframe
                v-if="TRUSTINDEX_WIDGET_ID"
                ref="frame"
                :srcdoc="srcdoc"
                :style="{ height: `min(${height}px, var(--reviews-height))` }"
                class="relative z-10 block w-full border-0"
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

<script setup>
import { watch } from 'vue';
import { WHATSAPP } from '../data/site.js';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
});
const emit = defineEmits(['close']);

// Trava o scroll do body quando o modal esta aberto.
watch(
    () => props.open,
    (v) => {
        if (typeof document !== 'undefined') {
            document.body.style.overflow = v ? 'hidden' : '';
        }
    },
);
</script>

<template>
    <transition name="modal">
        <div v-if="open" class="fixed inset-0 z-[60] flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-black/50" @click="emit('close')"></div>
            <div class="relative w-full max-w-lg rounded-lg bg-white p-8 shadow-2xl">
                <button
                    class="absolute right-4 top-4 text-heading transition hover:text-brand-dark"
                    aria-label="Fechar"
                    @click="emit('close')"
                >
                    <i class="fa-solid fa-xmark text-[26px]"></i>
                </button>
                <h3 class="text-[24px] font-extrabold text-heading">{{ title }}</h3>
                <p class="mt-3 text-[15px] leading-relaxed text-ink">
                    Fale com a nossa equipe para conferir os valores e condições atualizados de
                    <strong>{{ title.toLowerCase() }}</strong>.
                </p>
                <a
                    :href="WHATSAPP.vendas"
                    target="_blank"
                    rel="noopener"
                    class="mt-6 inline-block rounded-[3px] bg-brand px-7 py-3 text-[15px] font-semibold uppercase tracking-wide text-white transition hover:brightness-105"
                >
                    Falar com a Central de vendas
                </a>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>

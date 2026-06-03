<script setup>
import { watch } from 'vue';
import { WHATSAPP } from '../data/site.js';

const props = defineProps({
    open: { type: Boolean, default: false },
    card: { type: Object, default: null },
    storeEnabled: { type: Boolean, default: true },
});
const emit = defineEmits(['close']);

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
            <div class="relative w-full max-w-[300px] rounded-[5px] bg-white px-5 pb-6 pt-8 text-center shadow-[0_8px_30px_rgba(0,0,0,0.18)] sm:max-w-[390px] sm:px-8">
                <button
                    class="absolute right-[6px] top-[6px] grid h-[30px] w-[30px] place-items-center text-[#4a4a4a] transition hover:text-brand"
                    aria-label="Fechar"
                    @click="emit('close')"
                >
                    <i class="fa-solid fa-xmark text-[22px]"></i>
                </button>

                <h3 class="font-poppins text-[20px] font-semibold leading-tight text-[#4a4a4a] sm:text-[22px]">
                    {{ storeEnabled ? 'Escolha o tipo de tratamento' : card?.title }}
                </h3>
                <p v-if="storeEnabled" class="mx-auto mt-3 max-w-[290px] font-montserrat text-[14px] leading-[1.55] text-[#7b7b7b]">
                    Isso nos ajuda a mostrar os pacotes ideais para você, com áreas e valores personalizados.
                </p>
                <p v-else class="mx-auto mt-3 max-w-[290px] font-montserrat text-[14px] leading-[1.55] text-[#7b7b7b]">
                    Fale com a nossa equipe para conferir os valores e condições atualizados de
                    <strong>{{ card?.title?.toLowerCase() }}</strong>.
                </p>

                <div v-if="storeEnabled" class="mt-6 grid gap-3">
                    <a
                        :href="card?.modal?.female || '/depilacao-feminina'"
                        class="flex h-[44px] items-center justify-center rounded-[3px] bg-brand px-5 font-poppins text-[14px] font-semibold uppercase text-white transition hover:brightness-105"
                    >
                        feminino
                    </a>
                    <a
                        :href="card?.modal?.male || '/depilacao-masculina'"
                        class="flex h-[44px] items-center justify-center rounded-[3px] bg-brand px-5 font-poppins text-[14px] font-semibold uppercase text-white transition hover:brightness-105"
                    >
                        masculino
                    </a>
                </div>

                <a
                    v-else
                    :href="WHATSAPP.vendas"
                    target="_blank"
                    rel="noopener"
                    class="mt-6 inline-flex h-[44px] items-center justify-center rounded-[3px] bg-brand px-6 font-poppins text-[13px] font-semibold uppercase tracking-wide text-white transition hover:brightness-105"
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

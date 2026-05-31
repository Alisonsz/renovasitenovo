<script setup>
import { ref } from 'vue';
import { FAQ, WHATSAPP } from '../data/site.js';

const track = ref(null);
function scroll(dir) {
    if (!track.value) return;
    track.value.scrollBy({ left: dir * track.value.clientWidth, behavior: 'smooth' });
}
</script>

<template>
    <section class="px-5 py-14 lg:py-20">
        <div class="mx-auto max-w-[1100px]">
            <h2 class="text-center text-[28px] font-extrabold text-heading lg:text-[39px]">Dúvidas frequentes</h2>

            <div class="relative mt-10 flex items-center gap-2">
                <button class="shrink-0 text-brand-dark lg:hidden" aria-label="Anterior" @click="scroll(-1)">
                    <i class="fa-solid fa-chevron-left text-[24px]"></i>
                </button>

                <ul
                    ref="track"
                    class="flex flex-1 snap-x snap-mandatory gap-5 overflow-x-auto scroll-smooth px-0 pb-4 [scrollbar-width:none] lg:grid lg:grid-cols-4 lg:overflow-visible"
                >
                    <li
                        v-for="item in FAQ"
                        :key="item.q"
                        class="flex w-[300px] shrink-0 snap-center flex-col rounded-[8px] bg-white px-[5px] py-[25px] text-center shadow-[0_0_10px_rgba(0,0,0,0.24)] lg:w-full"
                    >
                        <h3 class="text-[18px] font-bold leading-tight text-brand-dark">{{ item.q }}</h3>
                        <p class="mt-[15px] px-3 text-[16px] leading-relaxed text-muted">{{ item.a }}</p>
                    </li>
                </ul>

                <button class="shrink-0 text-brand-dark lg:hidden" aria-label="Próximo" @click="scroll(1)">
                    <i class="fa-solid fa-chevron-right text-[24px]"></i>
                </button>
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
                    class="mt-5 inline-block rounded-[3px] bg-brand px-8 py-3 font-poppins text-[15px] font-semibold uppercase tracking-wide text-white transition hover:brightness-105"
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

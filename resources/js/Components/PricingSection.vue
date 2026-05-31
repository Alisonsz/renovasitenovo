<script setup>
import { ref } from 'vue';
import PricingModal from './PricingModal.vue';
import { PRICING } from '../data/site.js';

const active = ref(null);
</script>

<template>
    <section class="bg-[linear-gradient(180deg,#FFFFFF_0%,#DBE8E9_100%)] px-5 py-14 lg:py-[120px]">
        <div class="mx-auto max-w-[1140px]">
            <div class="text-center">
                <h2 class="text-[28px] font-extrabold text-heading lg:text-[39px]">Conheça nossos preços</h2>
                <p class="mt-2 text-[18px] font-semibold text-brand-dark">Tem pacote, tem avulsa, tem pra você!</p>
            </div>

            <div class="relative mt-12">
                <ul
                    class="flex snap-x snap-mandatory gap-5 overflow-x-auto scroll-smooth px-0 pb-4 pl-10 pr-10 [scrollbar-width:none] lg:justify-center lg:overflow-visible lg:px-0"
                >
                    <li
                        v-for="card in PRICING"
                        :key="card.popup"
                        class="flex w-[270px] shrink-0 snap-center flex-col overflow-hidden rounded-[12px] bg-white shadow-[0_0_10px_rgba(0,0,0,0.27)] sm:w-[calc(50%-10px)] lg:w-[325px] lg:rounded-[8px]"
                    >
                        <div class="flex flex-col items-center px-0 pt-[35px]">
                            <img :src="card.image" :alt="card.title" class="h-[150px] w-[150px] rounded-full object-cover" />
                            <h3 class="mt-4 px-5 text-center text-[22px] font-extrabold leading-tight text-heading">{{ card.title }}</h3>
                            <p class="mt-3 min-h-[88px] px-5 pb-3 text-center text-[15px] leading-relaxed text-muted lg:px-[30px]">{{ card.text }}</p>
                        </div>
                        <button
                            class="mt-auto h-[57px] w-full rounded-b-[12px] bg-brand font-poppins text-[15px] font-semibold leading-[15px] text-white transition hover:brightness-105 lg:h-11 lg:rounded-b-[8px] lg:leading-normal"
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

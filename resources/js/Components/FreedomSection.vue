<script setup>
import { ref } from 'vue';
import { useScrollTranslateX } from '../Composables/useScrollTranslateX.js';

const firstLine = ref(null);
const secondLine = ref(null);
const thirdLine = ref(null);

const firstMotion = useScrollTranslateX(firstLine, { base: 0, speed: 0.5, direction: 1, max: 22 });
const secondMotion = useScrollTranslateX(secondLine, { base: 0, speed: 0.5, direction: -1, max: 22 });
const thirdMotion = useScrollTranslateX(thirdLine, { base: 0, speed: 1.1, direction: 1, max: 24 });
const firstStyle = firstMotion.style;
const secondStyle = secondMotion.style;
const thirdStyle = thirdMotion.style;
</script>

<template>
    <section class="block bg-white lg:hidden">
        <div class="mx-auto flex h-[430px] max-w-[390px] flex-col justify-center overflow-hidden px-5 text-center text-[25px] leading-[38px] text-brand-dark">
            <p
                ref="firstLine"
                class="inline-block font-extrabold transition-transform duration-150 ease-out"
                :style="firstStyle"
            >
                Você livre dos pelos
            </p>
            <p
                ref="secondLine"
                class="mt-[48px] inline-block font-normal transition-transform duration-150 ease-out"
                :style="secondStyle"
            >
                Conforto, cuidado e resultados.
            </p>
            <p
                ref="thirdLine"
                class="mt-[48px] inline-block font-extrabold transition-transform duration-150 ease-out"
                :style="thirdStyle"
            >
                Mais tempo pra você
            </p>
        </div>
    </section>
</template>

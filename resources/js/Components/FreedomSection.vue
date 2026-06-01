<script setup>
import { ref } from 'vue';
import { useScrollTranslateX } from '../Composables/useScrollTranslateX.js';

const firstLine = ref(null);
const secondLine = ref(null);
const thirdLine = ref(null);

const firstMotion = useScrollTranslateX(firstLine, { base: 25, speed: 0.5, direction: 1, max: 54 });
const secondMotion = useScrollTranslateX(secondLine, { base: 18, speed: 0.5, direction: -1, max: 54 });
const thirdMotion = useScrollTranslateX(thirdLine, { base: 92, speed: 2, direction: 1, max: 74 });
const firstStyle = firstMotion.style;
const secondStyle = secondMotion.style;
const thirdStyle = thirdMotion.style;
</script>

<template>
    <section class="block bg-white lg:hidden">
        <div class="mx-auto flex h-[364px] max-w-[390px] flex-col justify-center overflow-hidden text-[18px] leading-[27px] text-brand-dark">
            <p
                ref="firstLine"
                class="font-bold transition-transform duration-150 ease-out"
                :style="firstStyle"
            >
                Você livre dos pelos
            </p>
            <p
                ref="secondLine"
                class="mt-[35px] transition-transform duration-150 ease-out"
                :style="secondStyle"
            >
                Conforto, cuidado e resultados.
            </p>
            <p
                ref="thirdLine"
                class="mt-[34px] font-bold transition-transform duration-150 ease-out"
                :style="thirdStyle"
            >
                Mais tempo pra você
            </p>
        </div>
    </section>
</template>

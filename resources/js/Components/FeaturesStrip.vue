<script setup>
import { computed, ref } from 'vue';
import { FEATURES } from '../data/site.js';
import { useDragScroll } from '../Composables/useDragScroll.js';

const track = ref(null);

// Triple the items so the loop wraps seamlessly between identical sets.
const loopFeatures = computed(() => Array.from({ length: 3 }, () => FEATURES).flat());

useDragScroll(track, { autoplayMs: 3500 });
</script>

<template>
    <div class="relative z-20 mx-auto -mt-[84px] w-full max-w-none px-0 lg:max-w-[1180px] lg:px-2">
        <div class="flex items-center gap-1 sm:gap-2">
            <div class="flex-1 cursor-grab overflow-hidden pt-4 pb-8 outline-none ring-0 active:cursor-grabbing lg:pb-6">
                <div
                    ref="track"
                    class="-my-8 overflow-x-auto overflow-y-hidden scroll-smooth py-8 [scrollbar-width:none] [touch-action:pan-x_pan-y]"
                >
                    <ul class="flex select-none items-stretch">
                        <li
                            v-for="(feature, i) in loopFeatures"
                            :key="i + '-' + feature.title"
                            data-carousel-item
                            class="flex w-full shrink-0 justify-center border-0 px-0 outline-none ring-0 lg:w-1/4 lg:px-[10px]"
                        >
                            <div
                                class="flex h-[169px] w-[288px] max-w-[calc(100vw-64px)] flex-col items-center justify-center gap-2 rounded-[8px] border-0 bg-white px-[5px] py-[25px] text-center shadow-[0_0_10px_rgba(0,0,0,0.34)] outline-none ring-0 lg:h-full lg:w-full lg:max-w-none lg:rounded-[20px] lg:px-[17px] lg:py-[17px]"
                            >
                                <i :class="feature.icon" class="text-[30px] text-brand-dark"></i>
                                <p class="font-poppins text-[15px] leading-tight text-[#1d1d1d]">{{ feature.title }}</p>
                                <p class="font-montserrat text-[15px] leading-tight text-muted">{{ feature.text }}</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
div::-webkit-scrollbar {
    display: none;
}
</style>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { FEATURES } from '../data/site.js';

const GAP = 0; // o espaçamento visual fica no padding interno de cada slide
const INTERVAL = 3500; // autoplay 3,5s
const DRAG_THRESHOLD = 45;

const visible = ref(4); // 4 no desktop, 1 no mobile
const index = ref(0);
const animate = ref(true);
const stepPx = ref(0);
const track = ref(null);
const dragStartX = ref(0);
const dragDeltaX = ref(0);
const dragging = ref(false);
let timer = null;

// itens + clones (para loop infinito sem "salto" visivel)
const slides = computed(() => [...FEATURES, ...FEATURES.slice(0, visible.value)]);

const slideWidth = computed(
    () => `calc((100% - ${(visible.value - 1) * GAP}px) / ${visible.value})`,
);

const trackStyle = computed(() => ({
    transform: `translateX(${-(index.value * stepPx.value) + dragDeltaX.value}px)`,
    transition: animate.value && !dragging.value ? 'transform 0.6s ease' : 'none',
    columnGap: `${GAP}px`,
}));

function setVisible() {
    const v = window.matchMedia('(min-width: 1024px)').matches ? 4 : 1;
    if (v !== visible.value) {
        visible.value = v;
        index.value = 0;
    }
}

function measure() {
    if (!track.value) return;
    const first = track.value.querySelector('[data-slide]');
    if (first) stepPx.value = first.getBoundingClientRect().width + GAP;
}

function next() {
    index.value++;
}

function prev() {
    if (index.value <= 0) {
        animate.value = false;
        index.value = FEATURES.length;
        requestAnimationFrame(() =>
            requestAnimationFrame(() => {
                animate.value = true;
                index.value--;
            }),
        );
    } else {
        index.value--;
    }
}

function onTransitionEnd() {
    // ao chegar nos clones, reposiciona no inicio sem animacao
    if (index.value >= FEATURES.length) {
        animate.value = false;
        index.value = 0;
        requestAnimationFrame(() =>
            requestAnimationFrame(() => {
                animate.value = true;
            }),
        );
    }
}

function start() {
    stop();
    timer = setInterval(next, INTERVAL);
}
function stop() {
    if (timer) clearInterval(timer);
    timer = null;
}
function restart() {
    start();
}

function pointerX(event) {
    return event.touches?.[0]?.clientX ?? event.clientX;
}

function onDragStart(event) {
    dragging.value = true;
    dragStartX.value = pointerX(event);
    dragDeltaX.value = 0;
    stop();
}

function onDragMove(event) {
    if (!dragging.value) return;
    dragDeltaX.value = pointerX(event) - dragStartX.value;
}

function onDragEnd() {
    if (!dragging.value) return;

    const delta = dragDeltaX.value;
    dragging.value = false;
    dragDeltaX.value = 0;

    if (delta <= -DRAG_THRESHOLD) {
        next();
    } else if (delta >= DRAG_THRESHOLD) {
        prev();
    }

    restart();
}

function onResize() {
    setVisible();
    nextTick(measure);
}

onMounted(async () => {
    setVisible();
    await nextTick();
    measure();
    // remede apos carregar fontes/icones
    setTimeout(measure, 300);
    start();
    window.addEventListener('resize', onResize);
});

onBeforeUnmount(() => {
    stop();
    window.removeEventListener('resize', onResize);
});
</script>

<template>
    <div
        class="relative z-20 mx-auto -mt-[84px] max-w-[1180px] px-2"
        @touchstart.passive="onDragStart"
        @touchmove.passive="onDragMove"
        @touchend="onDragEnd"
        @touchcancel="onDragEnd"
    >
        <div class="flex items-center gap-1 sm:gap-2">
            <div
                class="-mx-2 flex-1 cursor-grab overflow-hidden py-4 outline-none ring-0 active:cursor-grabbing lg:mx-0"
                @mousedown="onDragStart"
                @mousemove="onDragMove"
                @mouseup="onDragEnd"
                @mouseleave="onDragEnd"
            >
                <ul
                    ref="track"
                    class="mx-2 flex select-none items-stretch outline-none ring-0 lg:mx-0"
                    :style="trackStyle"
                    @transitionend="onTransitionEnd"
                >
                    <li
                        v-for="(f, i) in slides"
                        :key="i"
                        data-slide
                        class="flex shrink-0 border-0 px-4 outline-none ring-0 lg:px-[10px]"
                        :style="{ width: slideWidth }"
                    >
                        <div
                            class="flex h-[169px] w-full flex-col items-center justify-center gap-2 rounded-[8px] border-0 bg-white px-[5px] py-[25px] text-center shadow-[0_0_10px_rgba(0,0,0,0.34)] outline-none ring-0 lg:h-full lg:rounded-[20px] lg:px-[17px] lg:py-[17px]"
                        >
                            <i :class="f.icon" class="text-[30px] text-brand-dark"></i>
                            <p class="font-poppins text-[15px] leading-tight text-[#1d1d1d]">{{ f.title }}</p>
                            <p class="font-montserrat text-[15px] leading-tight text-muted">{{ f.text }}</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

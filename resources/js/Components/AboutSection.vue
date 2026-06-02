<script setup>
import { ABOUT_PARAGRAPHS } from '../data/site.js';
</script>

<template>
    <section class="px-5 py-14 lg:py-20">
        <div class="mx-auto max-w-[820px] text-center">
            <h2 class="text-[28px] font-extrabold leading-tight text-heading-soft lg:text-[39px]">
                Na dúvida de qual clínica escolher?
            </h2>
            <h3 class="mt-1 text-[22px] font-semibold text-heading-soft lg:text-[28px]">
                Aposte em quem é referência
            </h3>

            <!-- linha verde: espacamento 35px topo/baixo -->
            <div class="mx-auto my-[35px] h-[2px] w-[96px] bg-brand-dark"></div>

            <div class="space-y-5 text-[16px] leading-relaxed text-ink">
                <p v-for="(p, i) in ABOUT_PARAGRAPHS" :key="i">{{ p }}</p>
            </div>
        </div>
    </section>
</template>

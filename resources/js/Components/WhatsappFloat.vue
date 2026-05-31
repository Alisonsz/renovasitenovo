<script setup>
import { WHATSAPP } from '../data/site.js';
</script>

<template>
    <a
        :href="WHATSAPP.especialista"
        target="_blank"
        rel="noopener"
        aria-label="Falar no WhatsApp"
        class="fixed bottom-5 right-5 z-50 grid h-14 w-14 place-items-center rounded-full bg-whats text-white shadow-lg shadow-black/25 transition hover:scale-105"
    >
        <i class="fa-brands fa-whatsapp text-[32px]"></i>
    </a>
</template>

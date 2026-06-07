<script setup>
import { Head } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';
import SiteLayout from '../Layouts/SiteLayout.vue';
import HeroSection from '../Components/HeroSection.vue';
import FeaturesStrip from '../Components/FeaturesStrip.vue';
import AboutSection from '../Components/AboutSection.vue';
import FreedomSection from '../Components/FreedomSection.vue';
import PricingSection from '../Components/PricingSection.vue';
import TestimonialsBand from '../Components/TestimonialsBand.vue';
import FaqSection from '../Components/FaqSection.vue';
import LocationSection from '../Components/LocationSection.vue';

// Anchor navigation (#precos, #contato). In an SPA the browser can't scroll to
// the hash on first load (the section doesn't exist yet) and image loading shifts
// layout — so we scroll a few times as the page settles.
function scrollToHash(smooth = false) {
    const hash = window.location.hash;
    if (!hash || hash.length < 2) return;

    try {
        const el = document.querySelector(hash);
        if (el) {
            el.scrollIntoView({ behavior: smooth ? 'smooth' : 'auto', block: 'start' });
        }
    } catch {
        // invalid selector — ignore
    }
}

function onHashChange() {
    scrollToHash(true);
}

onMounted(() => {
    if (window.location.hash) {
        [60, 300, 700].forEach((delay) => setTimeout(() => scrollToHash(false), delay));
    }
    window.addEventListener('hashchange', onHashChange);
});

onBeforeUnmount(() => {
    window.removeEventListener('hashchange', onHashChange);
});
</script>

<template>
    <Head title="Início" />
    <SiteLayout>
        <HeroSection />
        <FeaturesStrip />
        <AboutSection />
        <FreedomSection />
        <PricingSection />
        <TestimonialsBand />
        <FaqSection />
        <LocationSection />
    </SiteLayout>
</template>

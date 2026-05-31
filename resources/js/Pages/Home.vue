<script setup>
import { Head } from '@inertiajs/vue3';
import SiteLayout from '../Layouts/SiteLayout.vue';
import HeroSection from '../Components/HeroSection.vue';
import FeaturesStrip from '../Components/FeaturesStrip.vue';
import AboutSection from '../Components/AboutSection.vue';
import FreedomSection from '../Components/FreedomSection.vue';
import PricingSection from '../Components/PricingSection.vue';
import TestimonialsBand from '../Components/TestimonialsBand.vue';
import FaqSection from '../Components/FaqSection.vue';
import LocationSection from '../Components/LocationSection.vue';
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

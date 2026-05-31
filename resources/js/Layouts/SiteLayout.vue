<script setup>
import SiteHeader from '../Components/SiteHeader.vue';
import SiteFooter from '../Components/SiteFooter.vue';
import WhatsappFloat from '../Components/WhatsappFloat.vue';

defineProps({
    headerVariant: { type: String, default: 'default' },
});
</script>

<template>
    <div class="min-h-screen bg-white font-sans">
        <SiteHeader :variant="headerVariant" />
        <main>
            <slot />
        </main>
        <SiteFooter />
        <WhatsappFloat />
    </div>
</template>

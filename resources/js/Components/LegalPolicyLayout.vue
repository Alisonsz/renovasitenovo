<script setup>
import { Head } from '@inertiajs/vue3';
import SiteLayout from '../Layouts/SiteLayout.vue';

defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
    canonical: { type: String, required: true },
    content: { type: String, required: true },
});
</script>

<template>
    <Head :title="title">
        <meta head-key="description" name="description" :content="description">
        <link head-key="canonical" rel="canonical" :href="canonical">
        <meta head-key="og:title" property="og:title" :content="`${title} - Renova Laser Depilação`">
        <meta head-key="og:description" property="og:description" :content="description">
        <meta head-key="og:type" property="og:type" content="article">
        <meta head-key="og:url" property="og:url" :content="canonical">
    </Head>

    <SiteLayout header-variant="store">
        <section class="bg-white px-5 py-[42px] lg:py-[58px]">
            <article
                class="mx-auto max-w-[920px] font-sans text-[16px] leading-[1.5] text-[#333] [&_a]:font-semibold [&_a]:text-brand-dark [&_a]:underline-offset-2 [&_a:hover]:text-brand [&_a:hover]:underline [&_br]:block [&_br]:content-[''] [&_h5]:mb-[10px] [&_h5]:mt-[24px] [&_h5]:font-sans [&_h5]:text-[16px] [&_h5]:font-semibold [&_h5]:leading-[1.2] [&_h5]:text-[#333] [&_h5:first-child]:mt-0 [&_hr]:my-[22px] [&_hr]:border-0 [&_hr]:border-t [&_hr]:border-[#e5e5e5] [&_li]:mb-[8px] [&_ol]:mb-[18px] [&_ol]:mt-[10px] [&_ol]:list-decimal [&_ol]:space-y-[8px] [&_ol]:pl-[24px] [&_p]:mb-[16px] [&_strong]:font-semibold [&_ul]:mb-[18px] [&_ul]:mt-[10px] [&_ul]:list-disc [&_ul]:space-y-[8px] [&_ul]:pl-[24px]"
                v-html="content"
            ></article>
        </section>
    </SiteLayout>
</template>

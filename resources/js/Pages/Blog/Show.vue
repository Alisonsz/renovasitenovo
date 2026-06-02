<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import SiteLayout from '../../Layouts/SiteLayout.vue';

const props = defineProps({
    post: { type: Object, required: true },
    related: { type: Array, default: () => [] },
    structuredData: { type: Object, required: true },
});

const jsonLd = computed(() => JSON.stringify(props.structuredData));

function formatDate(value) {
    if (!value) return '';

    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(new Date(`${value}T12:00:00`));
}
</script>

<template>
    <Head :title="post.seo.title">
        <meta head-key="description" name="description" :content="post.seo.description">
        <meta head-key="robots" name="robots" :content="post.seo.robots">
        <link head-key="canonical" rel="canonical" :href="post.seo.canonical">
        <meta head-key="og:title" property="og:title" :content="post.seo.title">
        <meta head-key="og:description" property="og:description" :content="post.seo.description">
        <meta head-key="og:type" property="og:type" content="article">
        <meta head-key="og:url" property="og:url" :content="post.seo.canonical">
        <meta v-if="post.image_url" head-key="og:image" property="og:image" :content="post.image_url">
        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image">
        <component :is="'script'" head-key="article-json-ld" type="application/ld+json" v-html="jsonLd"></component>
    </Head>

    <SiteLayout header-variant="store">
        <article>
            <header class="bg-[linear-gradient(180deg,#e9f8f8_0%,#ffffff_78%)] px-5 pb-10 pt-16 lg:pb-14 lg:pt-20">
                <div class="mx-auto max-w-[920px] text-center">
                    <a href="/blog" class="font-montserrat text-[13px] font-bold uppercase tracking-[0.22em] text-brand-dark">Blog Renova Laser</a>
                    <h1 class="mt-4 font-poppins text-[31px] font-extrabold leading-tight text-heading lg:text-[48px]">
                        {{ post.title }}
                    </h1>
                    <p class="mx-auto mt-4 max-w-[780px] font-montserrat text-[17px] leading-relaxed text-muted">
                        {{ post.excerpt }}
                    </p>
                    <time class="mt-5 block font-montserrat text-[14px] font-medium text-[#8a9394]">{{ formatDate(post.published_at) }}</time>
                </div>
            </header>

            <div v-if="post.image_url" class="mx-auto max-w-[980px] px-5">
                <img
                    :src="post.image_url"
                    :alt="post.image_alt"
                    class="-mt-1 aspect-[16/8] w-full rounded-[8px] object-cover shadow-[0_10px_30px_rgba(0,0,0,0.13)]"
                    loading="eager"
                    decoding="async"
                >
            </div>

            <section class="px-5 py-12 lg:py-16">
                <div class="mx-auto grid max-w-[1140px] gap-10 lg:grid-cols-[minmax(0,760px)_280px] lg:justify-between">
                    <div class="blog-content rounded-[8px] bg-white font-montserrat text-[17px] leading-[1.85] text-[#3f4647] lg:px-2" v-html="post.content_html"></div>

                    <aside class="space-y-6 lg:sticky lg:top-6 lg:self-start">
                        <div v-if="post.categories?.length" class="rounded-[8px] bg-[#edf6f6] p-5 shadow-[0_8px_24px_rgba(0,0,0,0.06)]">
                            <h2 class="font-poppins text-[18px] font-extrabold text-heading">Categoria</h2>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a
                                    v-for="category in post.categories"
                                    :key="category.slug"
                                    :href="`/blog?categoria=${category.slug}`"
                                    class="rounded-full bg-white px-3 py-2 font-montserrat text-[13px] font-semibold text-brand-dark"
                                >
                                    {{ category.name }}
                                </a>
                            </div>
                        </div>

                        <div v-if="post.tags?.length" class="rounded-[8px] bg-white p-5 shadow-[0_8px_24px_rgba(0,0,0,0.10)] ring-1 ring-black/[0.03]">
                            <h2 class="font-poppins text-[18px] font-extrabold text-heading">Tags</h2>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span
                                    v-for="tag in post.tags"
                                    :key="tag.slug"
                                    class="rounded-full bg-[#edf6f6] px-3 py-2 font-montserrat text-[12px] font-semibold text-muted"
                                >
                                    {{ tag.name }}
                                </span>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>
        </article>

        <section v-if="related.length" class="bg-[#f6fbfb] px-5 py-12 lg:py-16">
            <div class="mx-auto max-w-[1140px]">
                <h2 class="text-center font-poppins text-[28px] font-extrabold text-heading lg:text-[36px]">Leia também</h2>
                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    <a
                        v-for="item in related"
                        :key="item.slug"
                        :href="item.href"
                        class="rounded-[8px] bg-white p-5 shadow-[0_5px_20px_rgba(0,0,0,0.10)] transition hover:-translate-y-1 hover:shadow-[0_14px_30px_rgba(0,0,0,0.13)]"
                    >
                        <time class="font-montserrat text-[12px] font-semibold text-brand-dark">{{ formatDate(item.published_at) }}</time>
                        <h3 class="mt-2 font-poppins text-[18px] font-extrabold leading-tight text-heading">{{ item.title }}</h3>
                    </a>
                </div>
            </div>
        </section>
    </SiteLayout>
</template>

<style scoped>
.blog-content :deep(h2),
.blog-content :deep(h3),
.blog-content :deep(h4) {
    color: #414445;
    font-family: Poppins, sans-serif;
    font-weight: 800;
    line-height: 1.25;
    margin: 2rem 0 0.85rem;
}

.blog-content :deep(h2) {
    font-size: clamp(1.55rem, 2.6vw, 2.15rem);
}

.blog-content :deep(h3) {
    font-size: clamp(1.3rem, 2vw, 1.65rem);
}

.blog-content :deep(p),
.blog-content :deep(ul),
.blog-content :deep(ol) {
    margin-bottom: 1.15rem;
}

.blog-content :deep(a) {
    color: #13b7b7;
    font-weight: 600;
}

.blog-content :deep(ul),
.blog-content :deep(ol) {
    padding-left: 1.4rem;
}

.blog-content :deep(img) {
    border-radius: 8px;
    height: auto;
    margin: 1.5rem auto;
    max-width: 100%;
}
</style>

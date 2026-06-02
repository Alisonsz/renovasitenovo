<script setup>
import { Head } from '@inertiajs/vue3';
import SiteLayout from '../../Layouts/SiteLayout.vue';

const props = defineProps({
    posts: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    activeCategory: { type: Object, default: null },
    seo: { type: Object, required: true },
});

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
    <Head :title="seo.title">
        <meta head-key="description" name="description" :content="seo.description">
        <link head-key="canonical" rel="canonical" :href="seo.canonical">
        <meta head-key="og:title" property="og:title" :content="seo.title">
        <meta head-key="og:description" property="og:description" :content="seo.description">
        <meta head-key="og:type" property="og:type" content="website">
        <meta head-key="og:url" property="og:url" :content="seo.canonical">
    </Head>

    <SiteLayout header-variant="store">
        <section class="bg-[linear-gradient(180deg,#e9f8f8_0%,#ffffff_78%)] px-5 pb-12 pt-16 lg:pb-16 lg:pt-20">
            <div class="mx-auto max-w-[980px] text-center">
                <p class="font-montserrat text-[13px] font-bold uppercase tracking-[0.22em] text-brand-dark">Blog Renova Laser</p>
                <h1 class="mt-3 font-poppins text-[34px] font-extrabold leading-tight text-heading lg:text-[48px]">
                    Conteúdos Renova Laser
                </h1>
                <p class="mx-auto mt-4 max-w-[720px] font-montserrat text-[16px] leading-relaxed text-muted lg:text-[18px]">
                    Guias e dicas sobre depilação a laser, tecnologia, cuidados com a pele e resultados.
                </p>
            </div>
        </section>

        <section class="px-5 py-10 lg:py-14">
            <div class="mx-auto max-w-[1140px]">
                <div class="mb-8 flex gap-3 overflow-x-auto pb-2 [scrollbar-width:none] sm:flex-wrap sm:justify-center sm:overflow-visible">
                    <a
                        href="/blog"
                        class="shrink-0 rounded-full px-4 py-2 font-montserrat text-[13px] font-semibold shadow-sm transition"
                        :class="!activeCategory ? 'bg-brand text-white' : 'bg-white text-brand-dark ring-1 ring-[#dbeaea] hover:bg-[#edf6f6]'"
                    >
                        Todos
                    </a>
                    <a
                        v-for="category in categories"
                        :key="category.slug"
                        :href="`/blog?categoria=${category.slug}`"
                        class="shrink-0 rounded-full px-4 py-2 font-montserrat text-[13px] font-semibold shadow-sm transition"
                        :class="activeCategory?.slug === category.slug ? 'bg-brand text-white' : 'bg-white text-brand-dark ring-1 ring-[#dbeaea] hover:bg-[#edf6f6]'"
                    >
                        {{ category.name }}
                    </a>
                </div>

                <div class="grid gap-7 md:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="post in posts.data"
                        :key="post.slug"
                        class="group flex overflow-hidden rounded-[8px] bg-white shadow-[0_8px_26px_rgba(0,0,0,0.10)] ring-1 ring-black/[0.03] transition duration-300 hover:-translate-y-1 hover:shadow-[0_16px_34px_rgba(0,0,0,0.13)]"
                    >
                        <a :href="post.href" class="flex w-full flex-col">
                            <div class="aspect-[16/10] overflow-hidden bg-[#d9fbf7]">
                                <img
                                    v-if="post.image_url"
                                    :src="post.image_url"
                                    :alt="post.image_alt"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
                                    loading="lazy"
                                    decoding="async"
                                >
                                <div v-else class="grid h-full place-items-center text-brand">
                                    <i class="fa-solid fa-spa text-[56px]"></i>
                                </div>
                            </div>
                            <div class="flex flex-1 flex-col p-6">
                                <time class="font-montserrat text-[12px] font-bold uppercase tracking-[0.08em] text-brand-dark">{{ formatDate(post.published_at) }}</time>
                                <h2 class="mt-3 font-poppins text-[21px] font-extrabold leading-tight text-heading lg:text-[22px]">
                                    {{ post.title }}
                                </h2>
                                <p class="mt-3 line-clamp-4 font-montserrat text-[15px] leading-relaxed text-muted">
                                    {{ post.excerpt }}
                                </p>
                                <span class="mt-auto inline-flex items-center gap-2 pt-5 font-poppins text-[14px] font-semibold text-brand-dark">
                                    Ler artigo
                                    <i class="fa-solid fa-arrow-right text-[12px]"></i>
                                </span>
                            </div>
                        </a>
                    </article>
                </div>

                <nav v-if="posts.links?.length" class="mt-10 flex flex-wrap justify-center gap-2">
                    <a
                        v-for="link in posts.links"
                        :key="`${link.label}-${link.url}`"
                        :href="link.url || '#'"
                        class="min-w-[38px] rounded-[4px] px-3 py-2 text-center font-montserrat text-[13px] font-semibold"
                        :class="[
                            link.active ? 'bg-brand text-white' : 'bg-[#edf6f6] text-brand-dark',
                            !link.url ? 'pointer-events-none opacity-45' : 'hover:bg-[#d9eeee]',
                        ]"
                        v-html="link.label"
                    ></a>
                </nav>
            </div>
        </section>
    </SiteLayout>
</template>

<style scoped>
div::-webkit-scrollbar {
    display: none;
}
</style>

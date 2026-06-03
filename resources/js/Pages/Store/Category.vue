<script setup>
import { Head } from '@inertiajs/vue3';
import { reactive } from 'vue';
import SiteLayout from '../../Layouts/SiteLayout.vue';
import ProductCard from '../../Components/Store/ProductCard.vue';

defineProps({
    category: { type: Object, required: true },
    children: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
});

const pageBySection = reactive({});
const perPage = 4;

function currentPage(section) {
    return pageBySection[section.slug] || 1;
}

function pageCount(section) {
    return Math.max(1, Math.ceil(section.products.length / perPage));
}

function visibleProducts(section) {
    const start = (currentPage(section) - 1) * perPage;

    return section.products.slice(start, start + perPage);
}

function setPage(section, page) {
    pageBySection[section.slug] = Math.min(Math.max(page, 1), pageCount(section));
}

function sectionAnchor(section) {
    if (section.slug.includes('pacotes')) return 'pacotes';
    if (section.slug.includes('avulsas') || section.slug.includes('sessoes-avulsas')) return 'avulsas';
    if (section.slug.includes('combos')) return 'combos';

    return section.slug;
}
</script>

<template>
    <Head :title="category.name" />

    <SiteLayout header-variant="store">
        <section
            v-for="(section, index) in sections"
            :key="section.id"
            :id="sectionAnchor(section)"
            class="px-5"
            :class="index === 0 ? 'bg-white pb-[42px] pt-[68px] lg:bg-[#eef8f8] lg:pb-[54px] lg:pt-[42px]' : 'bg-white py-[42px] lg:py-[62px]'"
        >
            <div class="mx-auto max-w-[1140px]">
                <header class="mx-auto max-w-[760px] text-center lg:mx-0 lg:text-left">
                    <h1 class="font-poppins text-[28px] font-extrabold leading-[1.08] text-[#363636] lg:text-[33px]">
                        {{ section.title }}
                    </h1>
                    <p v-if="section.subtitle" class="mt-[14px] font-montserrat text-[15px] leading-[1.55] text-[#7b7b7b] lg:mt-[18px] lg:text-[17px] lg:leading-[1.65]">
                        {{ section.subtitle }}
                    </p>
                    <div class="mx-auto mt-[22px] h-[2px] w-[91px] bg-brand lg:mx-0 lg:mt-[29px]"></div>
                </header>

                <div
                    v-if="section.products.length"
                    class="mt-[22px] grid grid-cols-2 justify-items-stretch gap-x-2 gap-y-3 sm:gap-x-5 sm:gap-y-[30px] lg:grid-cols-4"
                >
                    <ProductCard
                        v-for="product in visibleProducts(section)"
                        :key="product.id"
                        :product="product"
                        :button-label="section.button_label"
                    />
                </div>

                <nav
                    v-if="pageCount(section) > 1"
                    class="mt-[34px] flex items-center justify-center gap-[9px] font-poppins text-[14px] font-semibold"
                    :aria-label="`Paginação de ${section.title}`"
                >
                    <button
                        v-for="page in pageCount(section)"
                        :key="page"
                        type="button"
                        class="grid h-[36px] min-w-[36px] place-items-center rounded-full px-3 transition"
                        :class="currentPage(section) === page ? 'bg-brand text-white' : 'bg-white text-brand shadow-[0_1px_6px_rgba(0,0,0,0.16)] hover:bg-brand hover:text-white'"
                        @click="setPage(section, page)"
                    >
                        {{ page }}
                    </button>
                    <button
                        type="button"
                        class="h-[36px] rounded-full bg-brand px-[18px] text-white transition hover:brightness-105 disabled:opacity-50"
                        :disabled="currentPage(section) === pageCount(section)"
                        @click="setPage(section, currentPage(section) + 1)"
                    >
                        Próximo
                    </button>
                </nav>

                <div v-else class="mt-10 font-montserrat text-[16px] text-[#777]">
                    Nenhum produto ativo encontrado nesta categoria.
                </div>
            </div>
        </section>
    </SiteLayout>
</template>

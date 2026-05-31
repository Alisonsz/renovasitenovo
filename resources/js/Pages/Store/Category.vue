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
</script>

<template>
    <Head :title="category.name" />

    <SiteLayout header-variant="store">
        <section
            v-for="(section, index) in sections"
            :key="section.id"
            class="px-5"
            :class="index === 0 ? 'bg-[#eef8f8] pb-[54px] pt-[42px]' : 'bg-white py-[62px]'"
        >
            <div class="mx-auto max-w-[1140px]">
                <header class="max-w-[760px]">
                    <h1 class="font-poppins text-[28px] font-extrabold leading-tight text-[#363636] lg:text-[33px]">
                        {{ section.title }}
                    </h1>
                    <p v-if="section.subtitle" class="mt-[18px] font-montserrat text-[17px] leading-[1.65] text-[#7b7b7b]">
                        {{ section.subtitle }}
                    </p>
                    <div class="mt-[29px] h-[2px] w-[91px] bg-brand"></div>
                </header>

                <div
                    v-if="section.products.length"
                    class="mt-[22px] grid grid-cols-1 justify-items-center gap-x-5 gap-y-[30px] sm:grid-cols-2 lg:grid-cols-4"
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

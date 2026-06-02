<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import Pagination from '../../../Components/Admin/Pagination.vue';

defineProps({
    products: { type: Object, required: true },
});

function formatCents(cents) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format((cents || 0) / 100);
}
</script>

<template>
    <Head title="Produtos" />

    <AdminLayout>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-poppins text-[30px] font-extrabold text-[#363636]">Produtos</h1>
                <p class="mt-1 font-montserrat text-[15px] text-[#777]">Catálogo importado do WooCommerce.</p>
            </div>
            <a href="/admin/products/create" class="rounded-[3px] bg-brand px-5 py-3 font-poppins text-[14px] font-semibold text-white transition hover:brightness-105">
                Novo produto
            </a>
        </div>

        <div class="mt-8 overflow-hidden rounded-[6px] bg-white shadow-[0_0_10px_rgba(0,0,0,0.08)]">
            <table class="w-full min-w-[860px] border-collapse">
                <thead class="bg-[#f8fbfb] font-poppins text-[13px] uppercase text-[#777]">
                    <tr>
                        <th class="px-5 py-4 text-left">Produto</th>
                        <th class="px-5 py-4 text-left">Categorias</th>
                        <th class="px-5 py-4 text-left">Preço</th>
                        <th class="px-5 py-4 text-left">Status</th>
                        <th class="px-5 py-4 text-left">Merchant</th>
                        <th class="px-5 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="font-montserrat text-[14px] text-[#555]">
                    <tr v-for="product in products.data" :key="product.id" class="border-t border-[#edf1f1] transition hover:bg-[#fbfefe]">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <span class="grid h-[48px] w-[48px] shrink-0 place-items-center overflow-hidden rounded-[4px] bg-[#e4fbf8]">
                                    <img v-if="product.image_url" :src="product.image_url" :alt="product.name" class="h-full w-full object-cover">
                                    <i v-else class="fa-solid fa-spa text-brand"></i>
                                </span>
                                <div>
                                    <a :href="`/produto/${product.slug}`" class="font-poppins font-semibold text-[#333] transition hover:text-brand">
                                        {{ product.name }}
                                    </a>
                                    <p class="text-[12px] text-[#999]">{{ product.slug }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <span v-for="category in product.categories" :key="category.id" class="mr-1 inline-block rounded-full bg-[#e8f8f8] px-3 py-1 text-[12px] text-brand">
                                {{ category.name }}
                            </span>
                        </td>
                        <td class="px-5 py-4 font-semibold text-[#333]">{{ formatCents(product.price_cents) }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-3 py-1 text-[12px] font-semibold" :class="product.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'">
                                {{ product.is_active ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">{{ product.merchant_visibility }}</td>
                        <td class="px-5 py-4 text-right">
                            <a :href="`/admin/products/${product.id}/edit`" class="font-poppins text-[13px] font-semibold text-brand hover:underline">Editar</a>
                        </td>
                    </tr>
                    <tr v-if="!products.data.length"><td colspan="6" class="px-5 py-10 text-center text-[#888]">Nenhum produto encontrado.</td></tr>
                </tbody>
            </table>
        </div>

        <Pagination :meta="products" />
    </AdminLayout>
</template>

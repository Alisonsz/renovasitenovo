<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineProps({
    categories: { type: Array, required: true },
});
</script>

<template>
    <Head title="Categorias" />

    <AdminLayout>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-poppins text-[30px] font-extrabold text-[#363636]">Categorias</h1>
                <p class="mt-1 font-montserrat text-[15px] text-[#777]">Estrutura de categorias da loja.</p>
            </div>
            <button class="rounded-[3px] bg-brand px-5 py-3 font-poppins text-[14px] font-semibold text-white opacity-70" type="button">
                Nova categoria
            </button>
        </div>

        <div class="mt-8 overflow-hidden rounded-[6px] bg-white shadow-[0_0_10px_rgba(0,0,0,0.08)]">
            <table class="w-full min-w-[760px] border-collapse">
                <thead class="bg-[#f8fbfb] font-poppins text-[13px] uppercase text-[#777]">
                    <tr>
                        <th class="px-5 py-4 text-left">Nome</th>
                        <th class="px-5 py-4 text-left">Slug</th>
                        <th class="px-5 py-4 text-left">Pai</th>
                        <th class="px-5 py-4 text-left">Produtos</th>
                        <th class="px-5 py-4 text-left">Google</th>
                    </tr>
                </thead>
                <tbody class="font-montserrat text-[14px] text-[#555]">
                    <tr v-for="category in categories" :key="category.id" class="border-t border-[#edf1f1] transition hover:bg-[#fbfefe]">
                        <td class="px-5 py-4 font-poppins font-semibold text-[#333]">{{ category.name }}</td>
                        <td class="px-5 py-4">{{ category.slug }}</td>
                        <td class="px-5 py-4">{{ category.parent || '-' }}</td>
                        <td class="px-5 py-4">{{ category.products_count }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full bg-[#e8f8f8] px-3 py-1 text-[12px] text-brand">
                                {{ category.google_gender || 'unisex' }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

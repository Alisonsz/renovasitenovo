<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import Pagination from '../../../Components/Admin/Pagination.vue';

defineProps({
    posts: { type: Object, required: true },
});
</script>

<template>
    <Head title="Blog" />

    <AdminLayout>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-poppins text-[30px] font-extrabold text-[#363636]">Blog</h1>
                <p class="mt-1 font-montserrat text-[15px] text-[#777]">Posts importados do WordPress e novos conteúdos.</p>
            </div>
            <a href="/admin/blog-posts/create" class="rounded-[3px] bg-brand px-5 py-3 font-poppins text-[14px] font-semibold text-white transition hover:brightness-105">
                Novo post
            </a>
        </div>

        <div class="mt-8 overflow-hidden rounded-[6px] bg-white shadow-[0_0_10px_rgba(0,0,0,0.08)]">
            <table class="w-full min-w-[760px] border-collapse">
                <thead class="bg-[#f8fbfb] font-poppins text-[13px] uppercase text-[#777]">
                    <tr>
                        <th class="px-5 py-4 text-left">Título</th>
                        <th class="px-5 py-4 text-left">Slug</th>
                        <th class="px-5 py-4 text-left">Status</th>
                        <th class="px-5 py-4 text-left">Publicado</th>
                        <th class="px-5 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="font-montserrat text-[14px] text-[#555]">
                    <tr v-for="post in posts.data" :key="post.id" class="border-t border-[#edf1f1] transition hover:bg-[#fbfefe]">
                        <td class="px-5 py-4 font-poppins font-semibold text-[#333]">{{ post.title }}</td>
                        <td class="px-5 py-4">{{ post.slug }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-3 py-1 text-[12px] font-semibold" :class="post.status === 'publish' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'">
                                {{ post.status === 'publish' ? 'Publicado' : 'Rascunho' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">{{ post.published_at }}</td>
                        <td class="px-5 py-4 text-right">
                            <a :href="`/admin/blog-posts/${post.id}/edit`" class="font-poppins text-[13px] font-semibold text-brand hover:underline">Editar</a>
                        </td>
                    </tr>
                    <tr v-if="!posts.data.length"><td colspan="5" class="px-5 py-10 text-center text-[#888]">Nenhum post encontrado.</td></tr>
                </tbody>
            </table>
        </div>

        <Pagination :meta="posts" />
    </AdminLayout>
</template>

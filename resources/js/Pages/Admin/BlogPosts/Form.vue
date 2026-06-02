<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    post: { type: Object, default: null },
});

const form = useForm({
    title: props.post?.title || '',
    slug: props.post?.slug || '',
    excerpt: props.post?.excerpt || '',
    content_html: props.post?.content_html || '',
    status: props.post?.status || 'draft',
    published_at: props.post?.published_at || '',
    featured_image_url: props.post?.featured_image_url || '',
    featured_image_alt: props.post?.featured_image_alt || '',
    seo_title: props.post?.seo_title || '',
    seo_description: props.post?.seo_description || '',
    seo_focus_keyword: props.post?.seo_focus_keyword || '',
    canonical_url: props.post?.canonical_url || '',
    is_indexable: props.post?.is_indexable ?? true,
});

function submit() {
    if (props.post) {
        form.put(`/admin/blog-posts/${props.post.id}`);
        return;
    }

    form.post('/admin/blog-posts');
}

function destroyPost() {
    if (!props.post || !confirm('Excluir este post?')) return;
    router.delete(`/admin/blog-posts/${props.post.id}`);
}
</script>

<template>
    <Head :title="post ? 'Editar post' : 'Novo post'" />

    <AdminLayout>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-poppins text-[30px] font-extrabold text-[#363636]">{{ post ? 'Editar post' : 'Novo post' }}</h1>
                <p class="mt-1 font-montserrat text-[15px] text-[#777]">Mantenha slug, SEO e conteudo do blog em um unico lugar.</p>
            </div>
            <a href="/admin/blog-posts" class="font-poppins text-[14px] font-semibold text-brand">Voltar</a>
        </div>

        <form class="mt-8 grid gap-6 rounded-[6px] bg-white p-6 shadow-[0_8px_24px_rgba(0,0,0,0.08)] lg:grid-cols-[1fr_340px]" @submit.prevent="submit">
            <div class="space-y-6">
                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                    Titulo
                    <input v-model="form.title" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                    <span v-if="form.errors.title" class="text-[12px] text-red-600">{{ form.errors.title }}</span>
                </label>

                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                    Slug
                    <input v-model="form.slug" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                    <span v-if="form.errors.slug" class="text-[12px] text-red-600">{{ form.errors.slug }}</span>
                </label>

                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                    Resumo
                    <textarea v-model="form.excerpt" rows="4" class="mt-2 w-full rounded border border-[#dde6e6] px-3 py-2 outline-none focus:border-brand"></textarea>
                </label>

                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                    Conteudo HTML
                    <textarea v-model="form.content_html" rows="16" class="mt-2 w-full rounded border border-[#dde6e6] px-3 py-2 font-mono text-[13px] outline-none focus:border-brand"></textarea>
                </label>
            </div>

            <aside class="space-y-6">
                <section class="rounded-[5px] border border-[#edf1f1] p-4">
                    <h2 class="font-poppins text-[15px] font-bold text-[#363636]">Publicacao</h2>
                    <label class="mt-4 block font-montserrat text-[13px] font-semibold text-[#555]">
                        Status
                        <select v-model="form.status" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                            <option value="publish">Publicado</option>
                            <option value="draft">Rascunho</option>
                        </select>
                    </label>
                    <label class="mt-4 block font-montserrat text-[13px] font-semibold text-[#555]">
                        Data
                        <input v-model="form.published_at" type="datetime-local" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                    </label>
                    <label class="mt-4 flex items-center gap-2 font-montserrat text-[13px] font-semibold text-[#555]">
                        <input v-model="form.is_indexable" type="checkbox" class="accent-brand">
                        Indexavel
                    </label>
                </section>

                <section class="rounded-[5px] border border-[#edf1f1] p-4">
                    <h2 class="font-poppins text-[15px] font-bold text-[#363636]">Imagem</h2>
                    <label class="mt-4 block font-montserrat text-[13px] font-semibold text-[#555]">
                        URL
                        <input v-model="form.featured_image_url" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                    </label>
                    <label class="mt-4 block font-montserrat text-[13px] font-semibold text-[#555]">
                        Alt
                        <input v-model="form.featured_image_alt" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                    </label>
                </section>

                <section class="rounded-[5px] border border-[#edf1f1] p-4">
                    <h2 class="font-poppins text-[15px] font-bold text-[#363636]">SEO</h2>
                    <label class="mt-4 block font-montserrat text-[13px] font-semibold text-[#555]">
                        Titulo SEO
                        <input v-model="form.seo_title" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                    </label>
                    <label class="mt-4 block font-montserrat text-[13px] font-semibold text-[#555]">
                        Descricao
                        <textarea v-model="form.seo_description" rows="3" class="mt-2 w-full rounded border border-[#dde6e6] px-3 py-2 outline-none focus:border-brand"></textarea>
                    </label>
                    <label class="mt-4 block font-montserrat text-[13px] font-semibold text-[#555]">
                        Palavra-chave
                        <input v-model="form.seo_focus_keyword" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                    </label>
                    <label class="mt-4 block font-montserrat text-[13px] font-semibold text-[#555]">
                        Canonical
                        <input v-model="form.canonical_url" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                    </label>
                </section>

                <div class="flex flex-wrap gap-3">
                    <button class="rounded bg-brand px-5 py-3 font-poppins text-[14px] font-semibold text-white" :disabled="form.processing">
                        Salvar post
                    </button>
                    <button v-if="post" type="button" class="rounded border border-red-200 px-5 py-3 font-poppins text-[14px] font-semibold text-red-600" @click="destroyPost">
                        Excluir
                    </button>
                </div>
            </aside>
        </form>
    </AdminLayout>
</template>

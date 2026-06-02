<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    category: { type: Object, default: null },
    parents: { type: Array, default: () => [] },
});

const form = useForm({
    name: props.category?.name || '',
    slug: props.category?.slug || '',
    description: props.category?.description || '',
    parent_id: props.category?.parent_id || '',
    google_gender: props.category?.google_gender || '',
    merchant_visible: props.category?.merchant_visible ?? true,
    position: props.category?.position || 0,
});

function submit() {
    if (props.category) {
        form.put(`/admin/categories/${props.category.id}`);
        return;
    }

    form.post('/admin/categories');
}

function destroyCategory() {
    if (!props.category || !confirm('Excluir esta categoria?')) return;
    router.delete(`/admin/categories/${props.category.id}`);
}
</script>

<template>
    <Head :title="category ? 'Editar categoria' : 'Nova categoria'" />

    <AdminLayout>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-poppins text-[30px] font-extrabold text-[#363636]">{{ category ? 'Editar categoria' : 'Nova categoria' }}</h1>
                <p class="mt-1 font-montserrat text-[15px] text-[#777]">Organize seções da loja e atributos do Merchant.</p>
            </div>
            <a href="/admin/categories" class="font-poppins text-[14px] font-semibold text-brand">Voltar</a>
        </div>

        <form class="mt-8 grid gap-6 rounded-[6px] bg-white p-6 shadow-[0_8px_24px_rgba(0,0,0,0.08)] lg:grid-cols-2" @submit.prevent="submit">
            <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                Nome
                <input v-model="form.name" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                <span v-if="form.errors.name" class="text-[12px] text-red-600">{{ form.errors.name }}</span>
            </label>
            <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                Slug
                <input v-model="form.slug" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
            </label>
            <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                Categoria pai
                <select v-model="form.parent_id" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                    <option value="">Nenhuma</option>
                    <option v-for="parent in parents" :key="parent.id" :value="parent.id">{{ parent.name }}</option>
                </select>
            </label>
            <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                Google gender
                <select v-model="form.google_gender" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                    <option value="">Padrão</option>
                    <option value="female">female</option>
                    <option value="male">male</option>
                    <option value="unisex">unisex</option>
                </select>
            </label>
            <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                Ordem
                <input v-model="form.position" type="number" min="0" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
            </label>
            <label class="mt-8 flex items-center gap-2 font-montserrat text-[14px] font-semibold text-[#555]">
                <input v-model="form.merchant_visible" type="checkbox" class="accent-brand">
                Visível no Merchant
            </label>
            <label class="block font-montserrat text-[14px] font-semibold text-[#555] lg:col-span-2">
                Descrição
                <textarea v-model="form.description" rows="4" class="mt-2 w-full rounded border border-[#dde6e6] px-3 py-2 outline-none focus:border-brand"></textarea>
            </label>
            <div class="flex items-center gap-3 lg:col-span-2">
                <button class="rounded bg-brand px-6 py-3 font-poppins text-[14px] font-semibold text-white" :disabled="form.processing">
                    Salvar categoria
                </button>
                <button v-if="category" type="button" class="rounded border border-red-200 px-6 py-3 font-poppins text-[14px] font-semibold text-red-600" @click="destroyCategory">
                    Excluir
                </button>
            </div>
        </form>
    </AdminLayout>
</template>

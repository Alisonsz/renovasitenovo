<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    customer: { type: Object, default: null },
});

const isEdit = !!props.customer;
const photoPreview = ref(props.customer?.photo_url ?? null);

const form = useForm({
    _method: isEdit ? 'post' : 'post', // store uses POST; update route is POST too (multipart)
    name: props.customer?.name ?? '',
    email: props.customer?.email ?? '',
    phone: props.customer?.phone ?? '',
    document: props.customer?.document ?? '',
    birthdate: props.customer?.birthdate ?? '',
    instagram: props.customer?.instagram ?? '',
    address: props.customer?.address ?? '',
    notes: props.customer?.notes ?? '',
    is_active: props.customer?.is_active ?? true,
    photo: null,
});

function onPhoto(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.photo = file;
    photoPreview.value = URL.createObjectURL(file);
}

function submit() {
    const url = isEdit ? `/admin/customers/${props.customer.id}` : '/admin/customers';
    form.post(url, { forceFormData: true });
}

function destroyCustomer() {
    if (!isEdit || !confirm('Excluir este cliente? Esta ação não pode ser desfeita.')) return;
    router.delete(`/admin/customers/${props.customer.id}`);
}
</script>

<template>
    <Head :title="isEdit ? 'Editar cliente' : 'Novo cliente'" />
    <AdminLayout>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <Link href="/admin/customers" class="font-montserrat text-[13px] text-brand hover:underline">← Clientes</Link>
                <h1 class="mt-1 font-poppins text-[28px] font-extrabold text-[#363636]">{{ isEdit ? 'Editar cliente' : 'Novo cliente' }}</h1>
                <p class="font-montserrat text-[14px] text-[#777]">Apenas o nome é obrigatório.</p>
            </div>
        </div>

        <form class="mt-7 grid gap-6 lg:grid-cols-[260px_1fr]" @submit.prevent="submit">
            <!-- Photo -->
            <section class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                <p class="font-montserrat text-[14px] font-semibold text-[#555]">Foto</p>
                <div class="mt-3 grid place-items-center">
                    <div class="h-[150px] w-[150px] overflow-hidden rounded-full bg-[#e8f8f8] ring-1 ring-[#dce6e6]">
                        <img v-if="photoPreview" :src="photoPreview" alt="Foto" class="h-full w-full object-cover">
                        <span v-else class="grid h-full w-full place-items-center text-[40px] text-brand"><i class="fa-solid fa-user"></i></span>
                    </div>
                    <label class="mt-4 cursor-pointer rounded border border-[#dde6e6] px-4 py-2 font-poppins text-[13px] font-semibold text-[#555] transition hover:border-brand hover:text-brand">
                        Escolher foto
                        <input type="file" accept="image/*" class="hidden" @change="onPhoto">
                    </label>
                    <span v-if="form.errors.photo" class="mt-1 text-[12px] text-red-600">{{ form.errors.photo }}</span>
                </div>
            </section>

            <!-- Fields -->
            <section class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                <div class="grid gap-5 sm:grid-cols-2">
                    <label class="block font-montserrat text-[14px] font-semibold text-[#555] sm:col-span-2">
                        Nome *
                        <input v-model="form.name" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                        <span v-if="form.errors.name" class="text-[12px] text-red-600">{{ form.errors.name }}</span>
                    </label>
                    <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                        Telefone / WhatsApp
                        <input v-model="form.phone" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                    </label>
                    <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                        E-mail
                        <input v-model="form.email" type="email" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                        <span v-if="form.errors.email" class="text-[12px] text-red-600">{{ form.errors.email }}</span>
                    </label>
                    <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                        CPF
                        <input v-model="form.document" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                    </label>
                    <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                        Data de nascimento
                        <input v-model="form.birthdate" type="date" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                    </label>
                    <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                        Instagram
                        <input v-model="form.instagram" placeholder="@cliente" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                    </label>
                    <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                        <span class="opacity-0">.</span>
                        <span class="mt-2 flex h-[44px] items-center gap-2"><input v-model="form.is_active" type="checkbox" class="accent-brand"> Cliente ativo</span>
                    </label>
                    <label class="block font-montserrat text-[14px] font-semibold text-[#555] sm:col-span-2">
                        Endereço
                        <input v-model="form.address" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                    </label>
                    <label class="block font-montserrat text-[14px] font-semibold text-[#555] sm:col-span-2">
                        Observações
                        <textarea v-model="form.notes" rows="4" class="mt-2 w-full rounded border border-[#dde6e6] px-3 py-2 outline-none focus:border-brand"></textarea>
                    </label>
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <button :disabled="form.processing" class="h-[46px] rounded bg-brand px-7 font-poppins text-[15px] font-semibold text-white transition hover:brightness-105 disabled:opacity-60">
                        {{ isEdit ? 'Salvar' : 'Cadastrar cliente' }}
                    </button>
                    <button v-if="isEdit" type="button" @click="destroyCustomer" class="h-[46px] rounded border border-red-300 px-5 font-poppins text-[14px] font-semibold text-red-600 transition hover:bg-red-50">Excluir</button>
                </div>
            </section>
        </form>
    </AdminLayout>
</template>

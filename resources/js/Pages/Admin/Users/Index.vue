<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineProps({
    users: { type: Array, default: () => [] },
});

const editingId = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    is_admin: true,
});

function resetForm() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
}

function edit(u) {
    editingId.value = u.id;
    form.name = u.name;
    form.email = u.email;
    form.password = '';
    form.password_confirmation = '';
    form.is_admin = u.is_admin;
    form.clearErrors();
}

function submit() {
    if (editingId.value) {
        form.put(`/admin/usuarios/${editingId.value}`, { preserveScroll: true, onSuccess: resetForm });
    } else {
        form.post('/admin/usuarios', { preserveScroll: true, onSuccess: resetForm });
    }
}

function destroy(u) {
    if (!confirm(`Remover o usuário ${u.name}?`)) return;
    router.delete(`/admin/usuarios/${u.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Usuários" />
    <AdminLayout>
        <div>
            <h1 class="font-poppins text-[30px] font-extrabold text-[#363636]">Usuários administradores</h1>
            <p class="mt-1 font-montserrat text-[15px] text-[#777]">Quem pode acessar este painel.</p>
        </div>

        <form class="mt-8 grid gap-4 rounded-[6px] bg-white p-5 shadow-[0_0_10px_rgba(0,0,0,0.08)] lg:grid-cols-2" @submit.prevent="submit">
            <p v-if="editingId" class="lg:col-span-2 font-montserrat text-[13px] font-semibold text-brand">
                Editando usuário — <button type="button" class="underline" @click="resetForm">cancelar</button>
            </p>

            <label class="block font-montserrat text-[13px] font-semibold text-[#555]">
                Nome
                <input v-model="form.name" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                <span v-if="form.errors.name" class="text-[12px] text-red-600">{{ form.errors.name }}</span>
            </label>
            <label class="block font-montserrat text-[13px] font-semibold text-[#555]">
                E-mail
                <input v-model="form.email" type="email" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                <span v-if="form.errors.email" class="text-[12px] text-red-600">{{ form.errors.email }}</span>
            </label>
            <label class="block font-montserrat text-[13px] font-semibold text-[#555]">
                {{ editingId ? 'Nova senha (deixe em branco p/ manter)' : 'Senha' }}
                <input v-model="form.password" type="password" autocomplete="new-password" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                <span v-if="form.errors.password" class="text-[12px] text-red-600">{{ form.errors.password }}</span>
            </label>
            <label class="block font-montserrat text-[13px] font-semibold text-[#555]">
                Confirmar senha
                <input v-model="form.password_confirmation" type="password" autocomplete="new-password" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
            </label>
            <label class="flex items-center gap-2 font-montserrat text-[13px] font-semibold text-[#555] lg:col-span-2">
                <input v-model="form.is_admin" type="checkbox" class="accent-brand"> Acesso de administrador
                <span v-if="form.errors.is_admin" class="text-[12px] text-red-600">{{ form.errors.is_admin }}</span>
            </label>
            <div class="lg:col-span-2">
                <button :disabled="form.processing" class="rounded bg-brand px-6 py-3 font-poppins text-[14px] font-semibold text-white disabled:opacity-60">
                    {{ editingId ? 'Salvar' : 'Criar usuário' }}
                </button>
            </div>
        </form>

        <div class="mt-8 overflow-hidden rounded-[6px] bg-white shadow-[0_0_10px_rgba(0,0,0,0.08)]">
            <table class="w-full min-w-[640px] border-collapse">
                <thead class="bg-[#f8fbfb] font-poppins text-[13px] uppercase text-[#777]">
                    <tr>
                        <th class="px-5 py-4 text-left">Nome</th>
                        <th class="px-5 py-4 text-left">E-mail</th>
                        <th class="px-5 py-4 text-left">Acesso</th>
                        <th class="px-5 py-4 text-left">Criado</th>
                        <th class="px-5 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="font-montserrat text-[14px] text-[#555]">
                    <tr v-for="u in users" :key="u.id" class="border-t border-[#edf1f1] hover:bg-[#fbfefe]">
                        <td class="px-5 py-4 font-poppins font-semibold text-[#333]">
                            {{ u.name }}
                            <span v-if="u.is_self" class="ml-2 rounded-full bg-[#eefafa] px-2 py-0.5 text-[11px] font-semibold text-brand">você</span>
                        </td>
                        <td class="px-5 py-4">{{ u.email }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-3 py-1 text-[12px] font-semibold" :class="u.is_admin ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'">
                                {{ u.is_admin ? 'Administrador' : 'Sem acesso' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">{{ u.created_at }}</td>
                        <td class="px-5 py-4 text-right">
                            <button @click="edit(u)" class="font-poppins text-[13px] font-semibold text-brand hover:underline">Editar</button>
                            <button v-if="!u.is_self" @click="destroy(u)" class="ml-4 font-poppins text-[13px] font-semibold text-red-600 hover:underline">Excluir</button>
                        </td>
                    </tr>
                    <tr v-if="!users.length"><td colspan="5" class="px-5 py-10 text-center text-[#888]">Nenhum usuário.</td></tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

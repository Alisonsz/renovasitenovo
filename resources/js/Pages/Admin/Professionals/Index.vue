<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineProps({
    professionals: { type: Array, default: () => [] },
});

const editingId = ref(null);
const form = useForm({ name: '', role: '', color: '#29D8DB', phone: '', email: '', is_active: true });

function resetForm() { editingId.value = null; form.reset(); form.clearErrors(); }

function edit(p) {
    editingId.value = p.id;
    form.name = p.name; form.role = p.role ?? ''; form.color = p.color ?? '#29D8DB';
    form.phone = p.phone ?? ''; form.email = p.email ?? ''; form.is_active = p.is_active;
}

function submit() {
    if (editingId.value) form.put(`/admin/professionals/${editingId.value}`, { preserveScroll: true, onSuccess: resetForm });
    else form.post('/admin/professionals', { preserveScroll: true, onSuccess: resetForm });
}

function destroy(p) {
    if (!confirm(`Remover ${p.name}?`)) return;
    router.delete(`/admin/professionals/${p.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Profissionais" />
    <AdminLayout>
        <div>
            <h1 class="font-poppins text-[30px] font-extrabold text-[#363636]">Profissionais</h1>
            <p class="mt-1 font-montserrat text-[15px] text-[#777]">Quem atende na clínica (opcional nos agendamentos).</p>
        </div>

        <form class="mt-8 grid gap-4 rounded-[6px] bg-white p-5 shadow-[0_0_10px_rgba(0,0,0,0.08)] lg:grid-cols-6" @submit.prevent="submit">
            <p v-if="editingId" class="lg:col-span-6 font-montserrat text-[13px] font-semibold text-brand">Editando — <button type="button" class="underline" @click="resetForm">cancelar</button></p>
            <label class="block font-montserrat text-[13px] font-semibold text-[#555] lg:col-span-2">Nome
                <input v-model="form.name" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                <span v-if="form.errors.name" class="text-[12px] text-red-600">{{ form.errors.name }}</span>
            </label>
            <label class="block font-montserrat text-[13px] font-semibold text-[#555]">Função
                <input v-model="form.role" placeholder="Esteticista" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand"></label>
            <label class="block font-montserrat text-[13px] font-semibold text-[#555]">Cor
                <input v-model="form.color" type="color" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-1 outline-none"></label>
            <label class="block font-montserrat text-[13px] font-semibold text-[#555]">Telefone
                <input v-model="form.phone" class="mt-2 h-[42px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand"></label>
            <label class="mt-7 flex items-center gap-2 font-montserrat text-[13px] font-semibold text-[#555]">
                <input v-model="form.is_active" type="checkbox" class="accent-brand"> Ativo
            </label>
            <div class="lg:col-span-6">
                <button :disabled="form.processing" class="rounded bg-brand px-6 py-3 font-poppins text-[14px] font-semibold text-white disabled:opacity-60">{{ editingId ? 'Salvar' : 'Adicionar profissional' }}</button>
            </div>
        </form>

        <div class="mt-8 overflow-hidden rounded-[6px] bg-white shadow-[0_0_10px_rgba(0,0,0,0.08)]">
            <table class="w-full min-w-[640px] border-collapse">
                <thead class="bg-[#f8fbfb] font-poppins text-[13px] uppercase text-[#777]">
                    <tr><th class="px-5 py-4 text-left">Profissional</th><th class="px-5 py-4 text-left">Função</th><th class="px-5 py-4 text-center">Agendamentos</th><th class="px-5 py-4 text-left">Status</th><th class="px-5 py-4 text-right">Ações</th></tr>
                </thead>
                <tbody class="font-montserrat text-[14px] text-[#555]">
                    <tr v-for="p in professionals" :key="p.id" class="border-t border-[#edf1f1] hover:bg-[#fbfefe]">
                        <td class="px-5 py-4"><span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full" :style="{ background: p.color }"></span><span class="font-poppins font-semibold text-[#333]">{{ p.name }}</span></span></td>
                        <td class="px-5 py-4">{{ p.role || '—' }}</td>
                        <td class="px-5 py-4 text-center">{{ p.appointments_count }}</td>
                        <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-[12px] font-semibold" :class="p.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'">{{ p.is_active ? 'Ativo' : 'Inativo' }}</span></td>
                        <td class="px-5 py-4 text-right">
                            <button @click="edit(p)" class="font-poppins text-[13px] font-semibold text-brand hover:underline">Editar</button>
                            <button @click="destroy(p)" class="ml-4 font-poppins text-[13px] font-semibold text-red-600 hover:underline">Excluir</button>
                        </td>
                    </tr>
                    <tr v-if="!professionals.length"><td colspan="5" class="px-5 py-10 text-center text-[#888]">Nenhum profissional cadastrado.</td></tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

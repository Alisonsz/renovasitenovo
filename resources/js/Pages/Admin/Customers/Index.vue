<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import Pagination from '../../../Components/Admin/Pagination.vue';

const props = defineProps({
    customers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? '');
let t = null;
watch(search, () => {
    clearTimeout(t);
    t = setTimeout(() => router.get('/admin/customers', { search: search.value || undefined }, { preserveState: true, replace: true }), 300);
});

const fmt = (c) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format((c || 0) / 100);
</script>

<template>
    <Head title="Clientes" />
    <AdminLayout>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-poppins text-[30px] font-extrabold text-[#363636]">Clientes</h1>
                <p class="mt-1 font-montserrat text-[15px] text-[#777]">CRM — cadastro, tratamentos e histórico.</p>
            </div>
            <Link href="/admin/customers/create" class="rounded-[3px] bg-brand px-5 py-3 font-poppins text-[14px] font-semibold text-white transition hover:brightness-105">
                Novo cliente
            </Link>
        </div>

        <input v-model="search" placeholder="Buscar por nome, e-mail, telefone ou CPF" class="mt-6 h-[42px] w-full max-w-[460px] rounded border border-[#dde6e6] px-3 font-montserrat text-[14px] outline-none focus:border-brand">

        <div class="mt-6 overflow-hidden rounded-[6px] bg-white shadow-[0_0_10px_rgba(0,0,0,0.08)]">
            <table class="w-full min-w-[820px] border-collapse">
                <thead class="bg-[#f8fbfb] font-poppins text-[13px] uppercase text-[#777]">
                    <tr>
                        <th class="px-5 py-4 text-left">Cliente</th>
                        <th class="px-5 py-4 text-left">Contato</th>
                        <th class="px-5 py-4 text-center">Tratamentos</th>
                        <th class="px-5 py-4 text-center">Sessões</th>
                        <th class="px-5 py-4 text-left">Última visita</th>
                        <th class="px-5 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="font-montserrat text-[14px] text-[#555]">
                    <tr v-for="c in customers.data" :key="c.id" class="border-t border-[#edf1f1] transition hover:bg-[#fbfefe]">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <span class="grid h-[42px] w-[42px] shrink-0 place-items-center overflow-hidden rounded-full bg-[#e8f8f8]">
                                    <img v-if="c.photo_url" :src="c.photo_url" :alt="c.name" class="h-full w-full object-cover">
                                    <i v-else class="fa-solid fa-user text-brand"></i>
                                </span>
                                <p class="font-poppins font-semibold text-[#333]">{{ c.name }}</p>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <p>{{ c.phone || '—' }}</p>
                            <p class="text-[12px] text-[#888]">{{ c.email }}</p>
                        </td>
                        <td class="px-5 py-4 text-center">{{ c.treatments_count }}</td>
                        <td class="px-5 py-4 text-center">{{ c.appointments_count }}</td>
                        <td class="px-5 py-4">{{ c.last_visit_at || '—' }}</td>
                        <td class="px-5 py-4 text-right">
                            <Link :href="`/admin/customers/${c.id}`" class="font-poppins text-[13px] font-semibold text-brand hover:underline">Ver</Link>
                            <Link :href="`/admin/customers/${c.id}/edit`" class="ml-4 font-poppins text-[13px] font-semibold text-[#777] hover:underline">Editar</Link>
                        </td>
                    </tr>
                    <tr v-if="!customers.data.length"><td colspan="6" class="px-5 py-10 text-center text-[#888]">Nenhum cliente encontrado.</td></tr>
                </tbody>
            </table>
        </div>

        <Pagination :meta="customers" />
    </AdminLayout>
</template>

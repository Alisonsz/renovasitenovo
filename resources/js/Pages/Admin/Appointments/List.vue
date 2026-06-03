<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import Pagination from '../../../Components/Admin/Pagination.vue';

const props = defineProps({
    appointments: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    professionals: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    weekdays: { type: Array, default: () => [] },
});

const f = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    professional_id: props.filters.professional_id ?? '',
    date: props.filters.date ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    weekday: props.filters.weekday ?? '',
    sort: props.filters.sort ?? 'desc',
});

let t = null;
watch(f, () => {
    clearTimeout(t);
    t = setTimeout(() => {
        const params = {};
        for (const [k, v] of Object.entries(f)) {
            if (v !== '' && v !== null) params[k] = v;
        }
        router.get('/admin/appointments/list', params, { preserveState: true, replace: true });
    }, 300);
});

function clearFilters() {
    Object.assign(f, { search: '', status: '', professional_id: '', date: '', from: '', to: '', weekday: '', sort: 'desc' });
}

const statusStyle = (s) => ({
    scheduled: 'bg-blue-50 text-blue-700',
    confirmed: 'bg-teal-50 text-teal-700',
    completed: 'bg-green-50 text-green-700',
    no_show: 'bg-red-50 text-red-700',
    cancelled: 'bg-gray-100 text-gray-500',
}[s] || 'bg-gray-100 text-gray-600');

const statusLabel = (s) => ({
    scheduled: 'Agendado', confirmed: 'Confirmado', completed: 'Realizado',
    no_show: 'Faltou', cancelled: 'Cancelado',
}[s] || s);
</script>

<template>
    <Head title="Agendamentos" />
    <AdminLayout>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-poppins text-[30px] font-extrabold text-[#363636]">Agendamentos</h1>
                <p class="mt-1 font-montserrat text-[15px] text-[#777]">Lista geral com filtros por data, dia da semana e mais.</p>
            </div>
            <div class="flex gap-2">
                <Link href="/admin/appointments" class="rounded border border-[#dde6e6] px-4 py-2 font-poppins text-[13px] font-semibold text-[#555] transition hover:border-brand hover:text-brand">
                    <i class="fa-solid fa-calendar-days mr-1"></i> Calendário
                </Link>
                <Link href="/admin/appointments/create" class="rounded bg-brand px-4 py-2 font-poppins text-[13px] font-semibold text-white transition hover:brightness-105">+ Novo</Link>
            </div>
        </div>

        <!-- Filters -->
        <div class="mt-6 grid gap-3 rounded-[6px] bg-white p-4 shadow-[0_0_10px_rgba(0,0,0,0.08)] sm:grid-cols-2 lg:grid-cols-4">
            <label class="block font-montserrat text-[12px] font-semibold text-[#777]">
                Buscar cliente
                <input v-model="f.search" placeholder="Nome ou telefone" class="mt-1 h-[40px] w-full rounded border border-[#dde6e6] px-3 text-[14px] outline-none focus:border-brand">
            </label>
            <label class="block font-montserrat text-[12px] font-semibold text-[#777]">
                Status
                <select v-model="f.status" class="mt-1 h-[40px] w-full rounded border border-[#dde6e6] px-3 text-[14px] outline-none focus:border-brand">
                    <option value="">Todos</option>
                    <option v-for="s in statuses" :key="s" :value="s">{{ statusLabel(s) }}</option>
                </select>
            </label>
            <label class="block font-montserrat text-[12px] font-semibold text-[#777]">
                Profissional
                <select v-model="f.professional_id" class="mt-1 h-[40px] w-full rounded border border-[#dde6e6] px-3 text-[14px] outline-none focus:border-brand">
                    <option value="">Todos</option>
                    <option v-for="p in professionals" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
            </label>
            <label class="block font-montserrat text-[12px] font-semibold text-[#777]">
                Dia da semana
                <select v-model="f.weekday" class="mt-1 h-[40px] w-full rounded border border-[#dde6e6] px-3 text-[14px] outline-none focus:border-brand">
                    <option value="">Todos</option>
                    <option v-for="d in weekdays" :key="d.value" :value="d.value">{{ d.label }}</option>
                </select>
            </label>
            <label class="block font-montserrat text-[12px] font-semibold text-[#777]">
                Data exata
                <input v-model="f.date" type="date" class="mt-1 h-[40px] w-full rounded border border-[#dde6e6] px-3 text-[14px] outline-none focus:border-brand">
            </label>
            <label class="block font-montserrat text-[12px] font-semibold text-[#777]">
                De
                <input v-model="f.from" type="date" class="mt-1 h-[40px] w-full rounded border border-[#dde6e6] px-3 text-[14px] outline-none focus:border-brand">
            </label>
            <label class="block font-montserrat text-[12px] font-semibold text-[#777]">
                Até
                <input v-model="f.to" type="date" class="mt-1 h-[40px] w-full rounded border border-[#dde6e6] px-3 text-[14px] outline-none focus:border-brand">
            </label>
            <label class="block font-montserrat text-[12px] font-semibold text-[#777]">
                Ordem
                <select v-model="f.sort" class="mt-1 h-[40px] w-full rounded border border-[#dde6e6] px-3 text-[14px] outline-none focus:border-brand">
                    <option value="desc">Mais recentes</option>
                    <option value="asc">Mais antigos</option>
                </select>
            </label>
            <div class="flex items-end sm:col-span-2 lg:col-span-4">
                <button type="button" @click="clearFilters" class="font-montserrat text-[13px] font-semibold text-brand hover:underline">Limpar filtros</button>
            </div>
        </div>

        <!-- Table -->
        <div class="mt-6 overflow-hidden rounded-[6px] bg-white shadow-[0_0_10px_rgba(0,0,0,0.08)]">
            <table class="w-full min-w-[820px] border-collapse">
                <thead class="bg-[#f8fbfb] font-poppins text-[13px] uppercase text-[#777]">
                    <tr>
                        <th class="px-5 py-4 text-left">Data</th>
                        <th class="px-5 py-4 text-left">Horário</th>
                        <th class="px-5 py-4 text-left">Cliente</th>
                        <th class="px-5 py-4 text-left">Profissional</th>
                        <th class="px-5 py-4 text-left">Tratamento</th>
                        <th class="px-5 py-4 text-left">Status</th>
                        <th class="px-5 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="font-montserrat text-[14px] text-[#555]">
                    <tr v-for="a in appointments.data" :key="a.id" class="border-t border-[#edf1f1] transition hover:bg-[#fbfefe]">
                        <td class="px-5 py-4">
                            <p class="font-poppins font-semibold text-[#333]">{{ a.date }}</p>
                            <p class="text-[12px] text-[#999]">{{ a.weekday }}</p>
                        </td>
                        <td class="px-5 py-4">{{ a.time }}–{{ a.end_time }}</td>
                        <td class="px-5 py-4">
                            <Link v-if="a.customer.id" :href="`/admin/customers/${a.customer.id}`" class="font-semibold text-brand hover:underline">{{ a.customer.name }}</Link>
                            <span v-else>{{ a.customer.name }}</span>
                            <p class="text-[12px] text-[#888]">{{ a.customer.phone }}</p>
                        </td>
                        <td class="px-5 py-4">{{ a.professional || '—' }}</td>
                        <td class="px-5 py-4">
                            {{ a.treatment || 'Avulso' }}
                            <span v-if="a.session_number" class="text-[12px] text-[#999]"> · {{ a.session_number }}ª</span>
                        </td>
                        <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-[12px] font-semibold" :class="statusStyle(a.status)">{{ statusLabel(a.status) }}</span></td>
                        <td class="px-5 py-4 text-right">
                            <Link :href="`/admin/appointments/${a.id}/edit`" class="font-poppins text-[13px] font-semibold text-brand hover:underline">Editar</Link>
                        </td>
                    </tr>
                    <tr v-if="!appointments.data.length"><td colspan="7" class="px-5 py-10 text-center text-[#888]">Nenhum agendamento encontrado com esses filtros.</td></tr>
                </tbody>
            </table>
        </div>

        <Pagination :meta="appointments" />
    </AdminLayout>
</template>

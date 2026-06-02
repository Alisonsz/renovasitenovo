<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    customer: { type: Object, required: true },
    treatmentProducts: { type: Array, default: () => [] },
});

const fmt = (c) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format((c || 0) / 100);

const showAssoc = ref(false);
const assoc = useForm({ product_id: '', name: '', total_sessions: 10, session_duration_min: 30 });

// Prefill from chosen treatment product.
watch(() => assoc.product_id, (id) => {
    const p = props.treatmentProducts.find((x) => x.id === Number(id));
    if (p) { assoc.total_sessions = p.sessions_count; assoc.session_duration_min = p.session_duration_min; assoc.name = p.name; }
});

function saveAssoc() {
    assoc.post(`/admin/customers/${props.customer.id}/treatments`, {
        preserveScroll: true,
        onSuccess: () => { showAssoc.value = false; assoc.reset(); },
    });
}

const statusBadge = (s) => ({
    completed: 'bg-green-50 text-green-700', active: 'bg-[#eefafa] text-brand',
    cancelled: 'bg-gray-100 text-gray-500', scheduled: 'bg-blue-50 text-blue-700',
    confirmed: 'bg-teal-50 text-teal-700', no_show: 'bg-red-50 text-red-700',
}[s] || 'bg-gray-100 text-gray-600');
</script>

<template>
    <Head :title="customer.name" />
    <AdminLayout>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Link href="/admin/customers" class="font-montserrat text-[13px] text-brand hover:underline">← Clientes</Link>
            <div class="flex gap-3">
                <Link :href="`/admin/customers/${customer.id}/edit`" class="rounded border border-[#dde6e6] px-4 py-2 font-poppins text-[13px] font-semibold text-[#555] transition hover:border-brand hover:text-brand">Editar</Link>
                <Link :href="`/admin/appointments/create?customer=${customer.id}`" class="rounded bg-brand px-4 py-2 font-poppins text-[13px] font-semibold text-white transition hover:brightness-105">Agendar sessão</Link>
            </div>
        </div>

        <div class="mt-4 grid gap-6 lg:grid-cols-[300px_1fr]">
            <!-- Profile card -->
            <section class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                <div class="grid place-items-center text-center">
                    <div class="h-[120px] w-[120px] overflow-hidden rounded-full bg-[#e8f8f8] ring-1 ring-[#dce6e6]">
                        <img v-if="customer.photo_url" :src="customer.photo_url" :alt="customer.name" class="h-full w-full object-cover">
                        <span v-else class="grid h-full w-full place-items-center text-[36px] text-brand"><i class="fa-solid fa-user"></i></span>
                    </div>
                    <h1 class="mt-3 font-poppins text-[20px] font-extrabold text-[#363636]">{{ customer.name }}</h1>
                    <span class="mt-1 rounded-full px-3 py-1 text-[12px] font-semibold" :class="customer.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'">{{ customer.is_active ? 'Ativo' : 'Inativo' }}</span>
                </div>
                <dl class="mt-5 space-y-2 font-montserrat text-[14px] text-[#555]">
                    <div v-if="customer.phone" class="flex items-center gap-2"><i class="fa-solid fa-phone w-4 text-brand"></i>{{ customer.phone }}</div>
                    <div v-if="customer.email" class="flex items-center gap-2"><i class="fa-solid fa-envelope w-4 text-brand"></i>{{ customer.email }}</div>
                    <div v-if="customer.document" class="flex items-center gap-2"><i class="fa-solid fa-id-card w-4 text-brand"></i>{{ customer.document }}</div>
                    <div v-if="customer.birthdate" class="flex items-center gap-2"><i class="fa-solid fa-cake-candles w-4 text-brand"></i>{{ customer.birthdate }}</div>
                    <div v-if="customer.instagram" class="flex items-center gap-2"><i class="fa-brands fa-instagram w-4 text-brand"></i>{{ customer.instagram }}</div>
                    <div v-if="customer.address" class="flex items-center gap-2"><i class="fa-solid fa-location-dot w-4 text-brand"></i>{{ customer.address }}</div>
                </dl>
                <div v-if="customer.notes" class="mt-4 rounded bg-[#f8fbfb] p-3 font-montserrat text-[13px] text-[#666]">
                    <p class="font-semibold text-[#555]">Observações</p>
                    <p class="mt-1 whitespace-pre-line">{{ customer.notes }}</p>
                </div>
            </section>

            <!-- Right column -->
            <div class="space-y-6">
                <!-- Treatments -->
                <section class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                    <div class="flex items-center justify-between">
                        <h2 class="font-poppins text-[17px] font-bold text-[#333]">Tratamentos</h2>
                        <button @click="showAssoc = !showAssoc" class="rounded border border-[#dde6e6] px-3 py-1.5 font-poppins text-[13px] font-semibold text-brand transition hover:border-brand">+ Associar</button>
                    </div>

                    <form v-if="showAssoc" class="mt-4 grid gap-3 rounded bg-[#f8fbfb] p-4 sm:grid-cols-2" @submit.prevent="saveAssoc">
                        <label class="block font-montserrat text-[13px] font-semibold text-[#555] sm:col-span-2">
                            Produto-tratamento
                            <select v-model="assoc.product_id" class="mt-1 h-[40px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                                <option value="">— manual —</option>
                                <option v-for="p in treatmentProducts" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sessions_count }} sessões)</option>
                            </select>
                        </label>
                        <label v-if="!assoc.product_id" class="block font-montserrat text-[13px] font-semibold text-[#555] sm:col-span-2">
                            Nome do tratamento
                            <input v-model="assoc.name" class="mt-1 h-[40px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                        </label>
                        <label class="block font-montserrat text-[13px] font-semibold text-[#555]">
                            Nº de sessões
                            <input v-model="assoc.total_sessions" type="number" min="1" class="mt-1 h-[40px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                        </label>
                        <label class="block font-montserrat text-[13px] font-semibold text-[#555]">
                            Duração (min)
                            <input v-model="assoc.session_duration_min" type="number" min="15" step="15" class="mt-1 h-[40px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                        </label>
                        <div class="sm:col-span-2">
                            <button :disabled="assoc.processing" class="rounded bg-brand px-5 py-2 font-poppins text-[13px] font-semibold text-white disabled:opacity-60">Associar tratamento</button>
                        </div>
                    </form>

                    <div class="mt-4 space-y-3">
                        <div v-for="t in customer.treatments" :key="t.id" class="rounded border border-[#eef3f3] p-4">
                            <div class="flex items-center justify-between">
                                <p class="font-poppins font-semibold text-[#333]">{{ t.name }}</p>
                                <span class="rounded-full px-3 py-1 text-[12px] font-semibold" :class="statusBadge(t.status)">{{ t.status }}</span>
                            </div>
                            <div class="mt-3 flex items-center gap-3">
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-[#eef3f3]">
                                    <div class="h-full rounded-full bg-brand" :style="{ width: (t.completed_sessions / t.total_sessions * 100) + '%' }"></div>
                                </div>
                                <span class="font-montserrat text-[13px] text-[#666]">{{ t.completed_sessions }}/{{ t.total_sessions }} sessões</span>
                            </div>
                        </div>
                        <p v-if="!customer.treatments.length" class="font-montserrat text-[14px] text-[#999]">Nenhum tratamento associado.</p>
                    </div>
                </section>

                <!-- Appointments -->
                <section class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                    <h2 class="font-poppins text-[17px] font-bold text-[#333]">Sessões / agendamentos</h2>
                    <table class="mt-4 w-full border-collapse font-montserrat text-[14px] text-[#555]">
                        <tbody>
                            <tr v-for="a in customer.appointments" :key="a.id" class="border-t border-[#eee]">
                                <td class="py-2">{{ a.starts_at }}</td>
                                <td class="py-2 text-[#888]">{{ a.professional || '—' }}</td>
                                <td class="py-2 text-center">{{ a.session_number ? ('Sessão ' + a.session_number) : '' }}</td>
                                <td class="py-2 text-right"><span class="rounded-full px-3 py-1 text-[12px] font-semibold" :class="statusBadge(a.status)">{{ a.status }}</span></td>
                            </tr>
                            <tr v-if="!customer.appointments.length"><td class="py-4 text-center text-[#999]">Nenhuma sessão agendada.</td></tr>
                        </tbody>
                    </table>
                </section>

                <!-- Orders -->
                <section class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                    <h2 class="font-poppins text-[17px] font-bold text-[#333]">Pedidos</h2>
                    <table class="mt-4 w-full border-collapse font-montserrat text-[14px] text-[#555]">
                        <tbody>
                            <tr v-for="o in customer.orders" :key="o.id" class="border-t border-[#eee]">
                                <td class="py-2"><Link :href="`/admin/orders/${o.id}`" class="font-semibold text-brand hover:underline">{{ o.number }}</Link></td>
                                <td class="py-2">{{ o.payment_status }}</td>
                                <td class="py-2 text-right font-semibold text-[#333]">{{ fmt(o.total_cents) }}</td>
                                <td class="py-2 text-right text-[13px]">{{ o.created_at }}</td>
                            </tr>
                            <tr v-if="!customer.orders.length"><td class="py-4 text-center text-[#999]">Nenhum pedido.</td></tr>
                        </tbody>
                    </table>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>

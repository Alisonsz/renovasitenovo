<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    appointment: { type: Object, default: null },
    presetCustomerId: { type: Number, default: null },
    presetDate: { type: String, default: null },
    customers: { type: Array, default: () => [] },
    professionals: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    treatments: { type: Array, default: () => [] },
});

const isEdit = !!props.appointment;
const treatments = ref(props.treatments ?? []);

const form = useForm({
    customer_id: props.appointment?.customer_id ?? props.presetCustomerId ?? '',
    professional_id: props.appointment?.professional_id ?? '',
    treatment_id: props.appointment?.treatment_id ?? '',
    starts_at: props.appointment?.starts_at ?? props.presetDate ?? '',
    duration_min: props.appointment?.duration_min ?? 30,
    status: props.appointment?.status ?? 'scheduled',
    notes: props.appointment?.notes ?? '',
});

const statusForm = useForm({ status: props.appointment?.status ?? 'scheduled' });

// Load the chosen customer's active treatments to offer session linking.
async function loadTreatments(customerId) {
    if (!customerId) { treatments.value = []; return; }
    try {
        const res = await fetch(`/admin/customers/${customerId}/treatments-json`, { headers: { Accept: 'application/json' } });
        treatments.value = await res.json();
    } catch (e) { treatments.value = []; }
}

watch(() => form.customer_id, (id) => { if (!isEdit) form.treatment_id = ''; loadTreatments(id); });
watch(() => form.treatment_id, (id) => {
    const t = treatments.value.find((x) => x.id === Number(id));
    if (t && t.session_duration_min) form.duration_min = t.session_duration_min;
});

onMounted(() => { if (form.customer_id && !treatments.value.length) loadTreatments(form.customer_id); });

function submit() {
    if (isEdit) form.put(`/admin/appointments/${props.appointment.id}`);
    else form.post('/admin/appointments');
}

function setStatus(s) {
    statusForm.status = s;
    statusForm.put(`/admin/appointments/${props.appointment.id}/status`, { preserveScroll: true });
}

function destroyAppt() {
    if (!confirm('Remover este agendamento?')) return;
    router.delete(`/admin/appointments/${props.appointment.id}`);
}
</script>

<template>
    <Head :title="isEdit ? 'Editar agendamento' : 'Novo agendamento'" />
    <AdminLayout>
        <Link href="/admin/appointments" class="font-montserrat text-[13px] text-brand hover:underline">← Agenda</Link>
        <h1 class="mt-1 font-poppins text-[28px] font-extrabold text-[#363636]">{{ isEdit ? 'Editar agendamento' : 'Novo agendamento' }}</h1>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_300px]">
            <form class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]" @submit.prevent="submit">
                <div class="grid gap-5 sm:grid-cols-2">
                    <label class="block font-montserrat text-[14px] font-semibold text-[#555] sm:col-span-2">
                        Cliente *
                        <select v-model="form.customer_id" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                            <option value="">Selecione…</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}{{ c.phone ? ' · ' + c.phone : '' }}</option>
                        </select>
                        <span v-if="form.errors.customer_id" class="text-[12px] text-red-600">{{ form.errors.customer_id }}</span>
                    </label>

                    <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                        Data e hora *
                        <input v-model="form.starts_at" type="datetime-local" step="900" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                        <span v-if="form.errors.starts_at" class="text-[12px] text-red-600">{{ form.errors.starts_at }}</span>
                    </label>
                    <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                        Duração (min)
                        <select v-model.number="form.duration_min" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                            <option v-for="d in [15,30,45,60,75,90,120]" :key="d" :value="d">{{ d }} min</option>
                        </select>
                    </label>

                    <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                        Profissional (opcional)
                        <select v-model="form.professional_id" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                            <option value="">— sem profissional —</option>
                            <option v-for="p in professionals" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </label>
                    <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                        Tratamento / sessão (opcional)
                        <select v-model="form.treatment_id" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                            <option value="">— avulso —</option>
                            <option v-for="t in treatments" :key="t.id" :value="t.id">{{ t.name }} ({{ t.completed_sessions }}/{{ t.total_sessions }})</option>
                        </select>
                    </label>

                    <label class="block font-montserrat text-[14px] font-semibold text-[#555] sm:col-span-2">
                        Observações
                        <textarea v-model="form.notes" rows="3" class="mt-2 w-full rounded border border-[#dde6e6] px-3 py-2 outline-none focus:border-brand"></textarea>
                    </label>
                </div>

                <p v-if="form.errors.starts_at" class="mt-3 rounded bg-red-50 px-4 py-2 font-montserrat text-[13px] text-red-700">{{ form.errors.starts_at }}</p>

                <div class="mt-6 flex items-center gap-3">
                    <button :disabled="form.processing" class="h-[46px] rounded bg-brand px-7 font-poppins text-[15px] font-semibold text-white transition hover:brightness-105 disabled:opacity-60">{{ isEdit ? 'Salvar' : 'Agendar' }}</button>
                    <button v-if="isEdit" type="button" @click="destroyAppt" class="h-[46px] rounded border border-red-300 px-5 font-poppins text-[14px] font-semibold text-red-600 transition hover:bg-red-50">Remover</button>
                </div>
            </form>

            <!-- Status quick actions (edit only) -->
            <section v-if="isEdit" class="h-fit rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
                <h2 class="font-poppins text-[16px] font-bold text-[#333]">Status da sessão</h2>
                <p class="mt-1 font-montserrat text-[13px] text-[#888]">Marcar presença atualiza o progresso do tratamento.</p>
                <div class="mt-4 grid gap-2">
                    <button @click="setStatus('confirmed')" class="h-[42px] rounded border border-teal-200 bg-teal-50 font-poppins text-[13px] font-semibold text-teal-800 transition hover:bg-teal-100">Confirmar</button>
                    <button @click="setStatus('completed')" class="h-[42px] rounded border border-green-200 bg-green-50 font-poppins text-[13px] font-semibold text-green-800 transition hover:bg-green-100">✓ Compareceu (realizada)</button>
                    <button @click="setStatus('no_show')" class="h-[42px] rounded border border-red-200 bg-red-50 font-poppins text-[13px] font-semibold text-red-700 transition hover:bg-red-100">Faltou (no-show)</button>
                    <button @click="setStatus('cancelled')" class="h-[42px] rounded border border-gray-200 bg-gray-50 font-poppins text-[13px] font-semibold text-gray-600 transition hover:bg-gray-100">Cancelar</button>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>

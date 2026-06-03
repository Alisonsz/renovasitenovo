<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    appointments: { type: Array, default: () => [] },
    view: { type: String, default: 'day' },
    date: { type: String, required: true },
    rangeStart: { type: String, required: true },
    rangeEnd: { type: String, required: true },
    professionals: { type: Array, default: () => [] },
    professionalId: { type: Number, default: null },
    statuses: { type: Array, default: () => [] },
    hours: { type: Object, default: () => ({ start: 7, end: 22 }) },
});

const SLOT_MIN = 15;
const SLOT_PX = 16;            // integer height per 15-min slot (avoids sub-pixel drift)
const HEADER_PX = 42;         // day-column header height

// Build the time grid (07:00 → 22:00 in 15-min slots).
const slots = computed(() => {
    const out = [];
    for (let h = props.hours.start; h < props.hours.end; h++) {
        for (let m = 0; m < 60; m += SLOT_MIN) {
            out.push({ h, m, label: `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`, isHour: m === 0 });
        }
    }
    return out;
});

const gridHeight = computed(() => slots.value.length * SLOT_PX);

// Days shown: 1 (day view) or 7 (week view).
const days = computed(() => {
    if (props.view === 'day') return [props.date];
    const start = new Date(props.rangeStart + 'T00:00:00');
    return Array.from({ length: 7 }, (_, i) => {
        const d = new Date(start);
        d.setDate(start.getDate() + i);
        return d.toISOString().slice(0, 10);
    });
});

const dayLabel = (iso) => {
    const d = new Date(iso + 'T00:00:00');
    return d.toLocaleDateString('pt-BR', { weekday: 'short', day: '2-digit', month: '2-digit' });
};

const statusStyle = (s) => ({
    scheduled: 'bg-[#eef7ff] border-l-blue-400 text-blue-900',
    confirmed: 'bg-[#e9fbf7] border-l-teal-400 text-teal-900',
    completed: 'bg-[#eafaf0] border-l-green-500 text-green-900',
    no_show: 'bg-[#fdeef0] border-l-red-400 text-red-900 line-through',
    cancelled: 'bg-[#f2f2f2] border-l-gray-300 text-gray-400 line-through',
}[s] || 'bg-[#eef7ff] border-l-blue-400');

function apptsForDay(iso) {
    const items = props.appointments
        .filter((a) => a.date === iso)
        .map((a) => {
            const start = new Date(a.starts_at);
            const minsFromTop = (start.getHours() - props.hours.start) * 60 + start.getMinutes();
            const top = (minsFromTop / SLOT_MIN) * SLOT_PX;
            const height = Math.max(SLOT_PX - 1, (a.duration_min / SLOT_MIN) * SLOT_PX - 1);
            return { ...a, _start: start.getTime(), _end: start.getTime() + a.duration_min * 60000, top, height };
        })
        .sort((x, y) => x._start - y._start);

    // Lay out overlapping appointments side by side (column packing).
    const columns = []; // each column tracks the end time of its last item
    items.forEach((a) => {
        let placed = false;
        for (let i = 0; i < columns.length; i++) {
            if (columns[i] <= a._start) { a._col = i; columns[i] = a._end; placed = true; break; }
        }
        if (!placed) { a._col = columns.length; columns.push(a._end); }
    });
    const totalCols = Math.max(1, columns.length);
    items.forEach((a) => {
        // group concurrency = how many columns actually overlap this item
        const overlap = items.filter((b) => b._start < a._end && b._end > a._start);
        const cols = Math.max(...overlap.map((b) => b._col)) + 1;
        a._widthPct = 100 / cols;
        a._leftPct = a._col * a._widthPct;
    });
    return items;
}

function navigate(deltaDays) {
    const d = new Date(props.date + 'T00:00:00');
    d.setDate(d.getDate() + deltaDays);
    go({ date: d.toISOString().slice(0, 10) });
}

function go(params) {
    router.get('/admin/appointments', {
        view: props.view, date: props.date, professional_id: props.professionalId || undefined, ...params,
    }, { preserveState: true, replace: true });
}

const todayIso = () => new Date().toISOString().slice(0, 10);
const newAppointmentHref = computed(() => `/admin/appointments/create?date=${props.date}T09:00`);
</script>

<template>
    <Head title="Agenda" />
    <AdminLayout>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="font-poppins text-[30px] font-extrabold text-[#363636]">Agenda</h1>
                <p class="mt-1 font-montserrat text-[15px] text-[#777]">Atendimentos · slots de 15 min · {{ String(hours.start).padStart(2,'0') }}h–{{ hours.end }}h</p>
            </div>
            <div class="flex gap-2">
                <Link href="/admin/appointments/list" class="rounded-[3px] border border-[#dde6e6] px-4 py-3 font-poppins text-[14px] font-semibold text-[#555] transition hover:border-brand hover:text-brand">
                    <i class="fa-solid fa-list mr-1"></i> Lista
                </Link>
                <Link :href="newAppointmentHref" class="rounded-[3px] bg-brand px-5 py-3 font-poppins text-[14px] font-semibold text-white transition hover:brightness-105">+ Novo agendamento</Link>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="mt-6 flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1 rounded-[6px] bg-white p-1 shadow-sm">
                <button @click="navigate(view === 'week' ? -7 : -1)" class="grid h-9 w-9 place-items-center rounded text-[#666] hover:bg-[#f0fafa]"><i class="fa-solid fa-chevron-left"></i></button>
                <button @click="go({ date: todayIso() })" class="h-9 rounded px-3 font-poppins text-[13px] font-semibold text-[#555] hover:bg-[#f0fafa]">Hoje</button>
                <button @click="navigate(view === 'week' ? 7 : 1)" class="grid h-9 w-9 place-items-center rounded text-[#666] hover:bg-[#f0fafa]"><i class="fa-solid fa-chevron-right"></i></button>
            </div>

            <div class="flex items-center gap-1 rounded-[6px] bg-white p-1 shadow-sm">
                <button @click="go({ view: 'day' })" class="h-9 rounded px-4 font-poppins text-[13px] font-semibold transition" :class="view === 'day' ? 'bg-brand text-white' : 'text-[#555] hover:bg-[#f0fafa]'">Dia</button>
                <button @click="go({ view: 'week' })" class="h-9 rounded px-4 font-poppins text-[13px] font-semibold transition" :class="view === 'week' ? 'bg-brand text-white' : 'text-[#555] hover:bg-[#f0fafa]'">Semana</button>
            </div>

            <select :value="professionalId || ''" @change="go({ professional_id: $event.target.value || undefined })" class="h-[42px] rounded-[6px] border-0 bg-white px-3 font-montserrat text-[14px] text-[#555] shadow-sm outline-none">
                <option value="">Todos os profissionais</option>
                <option v-for="p in professionals" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>

            <p class="font-poppins text-[15px] font-semibold text-[#444]">
                {{ view === 'day' ? dayLabel(date) : `${dayLabel(rangeStart)} — ${dayLabel(rangeEnd)}` }}
            </p>
        </div>

        <!-- Calendar grid -->
        <div class="mt-5 overflow-x-auto rounded-[8px] bg-white shadow-[0_0_10px_rgba(0,0,0,0.08)]">
            <div class="flex min-w-[640px]">
                <!-- Time gutter -->
                <div class="w-[64px] shrink-0 border-r border-[#eef2f2]" :style="{ paddingTop: HEADER_PX + 'px' }">
                    <div v-for="s in slots" :key="s.label" class="relative" :style="{ height: SLOT_PX + 'px' }">
                        <span v-if="s.isHour" class="absolute -top-2 right-2 font-montserrat text-[11px] text-[#9aa]">{{ s.label }}</span>
                    </div>
                </div>

                <!-- Day columns -->
                <div class="flex flex-1">
                    <div v-for="iso in days" :key="iso" class="relative flex-1 border-r border-[#eef2f2] last:border-r-0">
                        <!-- header -->
                        <div class="sticky top-0 z-10 grid place-items-center border-b border-[#eef2f2] bg-[#f8fbfb] font-poppins text-[12px] font-bold uppercase tracking-wide" :class="iso === todayIso() ? 'text-brand' : 'text-[#888]'" :style="{ height: HEADER_PX + 'px' }">
                            {{ dayLabel(iso) }}
                        </div>
                        <!-- slot lines -->
                        <div class="relative" :style="{ height: gridHeight + 'px' }">
                            <div v-for="(s, i) in slots" :key="i" class="border-b" :class="s.isHour ? 'border-[#e6ecec]' : 'border-[#f4f8f8]'" :style="{ height: SLOT_PX + 'px' }"></div>

                            <!-- appointments -->
                            <Link
                                v-for="a in apptsForDay(iso)" :key="a.id"
                                :href="`/admin/appointments/${a.id}/edit`"
                                class="absolute overflow-hidden rounded-[4px] border-l-[3px] px-1.5 py-0.5 font-montserrat text-[11px] leading-tight shadow-sm transition hover:z-20 hover:shadow-md"
                                :class="statusStyle(a.status)"
                                :style="{ top: a.top + 'px', height: a.height + 'px', left: `calc(${a._leftPct}% + 2px)`, width: `calc(${a._widthPct}% - 4px)` }"
                                :title="`${a.time} ${a.customer.name}${a.treatment ? ' · ' + a.treatment.name : ''}`"
                            >
                                <p class="truncate font-semibold">{{ a.time }} {{ a.customer.name }}</p>
                                <p v-if="a.treatment && a.height > 28" class="truncate opacity-80">{{ a.treatment.name }}<span v-if="a.session_number"> · {{ a.session_number }}ª</span></p>
                                <p v-if="a.professional && a.height > 44" class="truncate opacity-70">{{ a.professional.name }}</p>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Day list (quick view of the day's appointments) -->
        <div v-if="view === 'day'" class="mt-6 rounded-[8px] bg-white p-5 shadow-[0_0_10px_rgba(0,0,0,0.08)]">
            <h2 class="font-poppins text-[16px] font-bold text-[#333]">Atendimentos do dia</h2>
            <div class="mt-3 space-y-2">
                <div v-for="a in apptsForDay(date)" :key="a.id" class="flex items-center justify-between rounded border border-[#eef2f2] px-4 py-2 font-montserrat text-[14px]">
                    <span class="flex items-center gap-3">
                        <strong class="font-poppins text-[#333]">{{ a.time }}</strong>
                        <Link :href="`/admin/customers/${a.customer.id}`" class="text-brand hover:underline">{{ a.customer.name }}</Link>
                        <span v-if="a.treatment" class="text-[#888]">· {{ a.treatment.name }}</span>
                    </span>
                    <span class="rounded-full px-3 py-1 text-[12px] font-semibold" :class="statusStyle(a.status)">{{ a.status }}</span>
                </div>
                <p v-if="!apptsForDay(date).length" class="font-montserrat text-[14px] text-[#999]">Nenhum atendimento neste dia.</p>
            </div>
        </div>
    </AdminLayout>
</template>

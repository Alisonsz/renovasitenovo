<script setup>
import { onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    error: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

const fileInput = ref(null);
const isDragging = ref(false); // files being dragged over the dropzone
const dragFrom = ref(null); // index of tile being reordered
const dragOver = ref(null); // index currently hovered while reordering
const busy = ref(false);

let uid = 0;
const objectUrls = new Set();

function makePreview(file) {
    const url = URL.createObjectURL(file);
    objectUrls.add(url);

    return url;
}

onBeforeUnmount(() => {
    objectUrls.forEach((u) => URL.revokeObjectURL(u));
    objectUrls.clear();
});

// Downscale + re-encode to WebP so uploads stay small (and under PHP limits).
// Falls back to the original file if the browser can't process it.
async function compress(file) {
    if (!file.type.startsWith('image/') || file.type === 'image/gif' || file.type === 'image/svg+xml') {
        return file;
    }

    try {
        const bitmap = await createImageBitmap(file);
        const maxDim = 1600;
        const scale = Math.min(1, maxDim / Math.max(bitmap.width, bitmap.height));
        const width = Math.round(bitmap.width * scale);
        const height = Math.round(bitmap.height * scale);

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(bitmap, 0, 0, width, height);
        bitmap.close?.();

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/webp', 0.82));
        if (!blob) {
            return file;
        }

        const base = (file.name || 'imagem').replace(/\.[^.]+$/, '') || 'imagem';

        return new File([blob], `${base}.webp`, { type: 'image/webp' });
    } catch {
        return file;
    }
}

async function addFiles(fileList) {
    const files = Array.from(fileList || []).filter((f) => f.type.startsWith('image/'));
    if (!files.length) {
        return;
    }

    busy.value = true;
    try {
        const items = [];
        for (const file of files) {
            const processed = await compress(file);
            items.push({ uid: `n${uid++}`, file: processed, url: makePreview(processed), isNew: true });
        }
        emit('update:modelValue', [...props.modelValue, ...items]);
    } finally {
        busy.value = false;
    }
}

function onPick(event) {
    addFiles(event.target.files);
    event.target.value = '';
}

function onDropFiles(event) {
    isDragging.value = false;
    addFiles(event.dataTransfer?.files);
}

function removeAt(index) {
    const item = props.modelValue[index];
    if (item?.isNew && item.url) {
        URL.revokeObjectURL(item.url);
        objectUrls.delete(item.url);
    }
    const next = props.modelValue.slice();
    next.splice(index, 1);
    emit('update:modelValue', next);
}

function move(from, to) {
    if (from === to || from == null || to == null) {
        return;
    }
    const next = props.modelValue.slice();
    const [moved] = next.splice(from, 1);
    next.splice(to, 0, moved);
    emit('update:modelValue', next);
}

function makeCover(index) {
    move(index, 0);
}

// --- Reorder via native drag & drop ---
function onTileDragStart(index, event) {
    dragFrom.value = index;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(index));
}
function onTileDragOver(index) {
    if (dragFrom.value !== null) {
        dragOver.value = index;
    }
}
function onTileDrop(index) {
    move(dragFrom.value, index);
    dragFrom.value = null;
    dragOver.value = null;
}
function onTileDragEnd() {
    dragFrom.value = null;
    dragOver.value = null;
}
</script>

<template>
    <div>
        <!-- Dropzone -->
        <button
            type="button"
            class="flex w-full flex-col items-center justify-center gap-2 rounded-[10px] border-2 border-dashed px-6 py-8 text-center transition"
            :class="isDragging ? 'border-brand bg-[#eafafa]' : 'border-[#cfe0e0] bg-[#f8fcfc] hover:border-brand hover:bg-[#f1fbfb]'"
            @click="fileInput?.click()"
            @dragover.prevent="isDragging = true"
            @dragenter.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onDropFiles"
        >
            <span class="grid h-12 w-12 place-items-center rounded-full bg-brand/10 text-brand">
                <i class="fa-solid fa-cloud-arrow-up text-[22px]"></i>
            </span>
            <span class="font-poppins text-[15px] font-semibold text-[#3a4a4a]">
                Arraste as fotos aqui ou <span class="text-brand underline">clique para enviar</span>
            </span>
            <span class="font-montserrat text-[12px] text-[#9aa]">JPG, PNG ou WebP — várias de uma vez. Otimizamos automaticamente.</span>
        </button>
        <input ref="fileInput" type="file" accept="image/*" multiple class="hidden" @change="onPick">

        <p v-if="error" class="mt-2 font-montserrat text-[13px] font-semibold text-[#c0392b]">{{ error }}</p>

        <div v-if="busy" class="mt-3 flex items-center gap-2 font-montserrat text-[13px] text-[#778]">
            <i class="fa-solid fa-spinner fa-spin text-brand"></i> Processando imagens...
        </div>

        <!-- Grid -->
        <div v-if="modelValue.length" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            <div
                v-for="(item, i) in modelValue"
                :key="item.uid || item.id"
                class="group relative aspect-square overflow-hidden rounded-[10px] border bg-[#f3f8f8] transition"
                :class="[
                    dragOver === i && dragFrom !== i ? 'border-brand ring-2 ring-brand/40' : 'border-[#e1ecec]',
                    dragFrom === i ? 'opacity-40' : '',
                ]"
                draggable="true"
                @dragstart="onTileDragStart(i, $event)"
                @dragover.prevent="onTileDragOver(i)"
                @drop.prevent="onTileDrop(i)"
                @dragend="onTileDragEnd"
            >
                <img :src="item.url" alt="" class="h-full w-full object-cover" draggable="false">

                <!-- Cover badge -->
                <span
                    v-if="i === 0"
                    class="absolute left-2 top-2 rounded-full bg-brand px-2 py-[3px] font-montserrat text-[11px] font-bold text-white shadow"
                >
                    <i class="fa-solid fa-star mr-1 text-[10px]"></i>Capa
                </span>

                <!-- Drag hint -->
                <span class="absolute right-2 top-2 grid h-7 w-7 cursor-grab place-items-center rounded-full bg-black/35 text-white opacity-0 transition group-hover:opacity-100" title="Arraste para reordenar">
                    <i class="fa-solid fa-up-down-left-right text-[12px]"></i>
                </span>

                <!-- Hover actions -->
                <div class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-1 bg-gradient-to-t from-black/65 to-transparent p-2 opacity-0 transition group-hover:opacity-100">
                    <button
                        v-if="i !== 0"
                        type="button"
                        class="rounded-full bg-white/90 px-2 py-1 font-montserrat text-[11px] font-semibold text-brand transition hover:bg-white"
                        title="Tornar capa"
                        @click.stop="makeCover(i)"
                    >
                        <i class="fa-solid fa-star mr-1 text-[10px]"></i>Capa
                    </button>
                    <span v-else></span>
                    <div class="flex items-center gap-1">
                        <button type="button" class="grid h-7 w-7 place-items-center rounded-full bg-white/90 text-[#555] transition hover:bg-white disabled:opacity-30" title="Mover para a esquerda" :disabled="i === 0" @click.stop="move(i, i - 1)">
                            <i class="fa-solid fa-chevron-left text-[12px]"></i>
                        </button>
                        <button type="button" class="grid h-7 w-7 place-items-center rounded-full bg-white/90 text-[#555] transition hover:bg-white disabled:opacity-30" title="Mover para a direita" :disabled="i === modelValue.length - 1" @click.stop="move(i, i + 1)">
                            <i class="fa-solid fa-chevron-right text-[12px]"></i>
                        </button>
                        <button type="button" class="grid h-7 w-7 place-items-center rounded-full bg-[#c0392b] text-white transition hover:brightness-110" title="Remover" @click.stop="removeAt(i)">
                            <i class="fa-solid fa-trash text-[11px]"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <p v-else class="mt-4 rounded-[8px] bg-[#f7fbfb] px-4 py-6 text-center font-montserrat text-[13px] text-[#9aa]">
            Nenhuma imagem ainda. Envie as fotos do produto acima.
        </p>
    </div>
</template>

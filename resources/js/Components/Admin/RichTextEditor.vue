<script setup>
import { onMounted, ref, watch } from 'vue';

/**
 * Lightweight WYSIWYG editor (no external deps). Produces clean HTML via a
 * contenteditable surface + a toolbar. v-model holds the HTML string.
 *
 * Why no library: keeps the bundle small and avoids npm/deploy risk. Uses
 * document.execCommand which, although deprecated, is supported across all
 * current browsers and is plenty for an admin content editor.
 */
const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Escreva aqui…' },
    minHeight: { type: String, default: '320px' },
});
const emit = defineEmits(['update:modelValue']);

const editor = ref(null);
const isEmpty = ref(true);

function syncEmpty() {
    const el = editor.value;
    isEmpty.value = !el || el.innerHTML.trim() === '' || el.innerHTML === '<br>';
}

function emitChange() {
    const el = editor.value;
    if (!el) return;
    syncEmpty();
    emit('update:modelValue', isEmpty.value ? '' : el.innerHTML);
}

function exec(command, value = null) {
    editor.value?.focus();
    document.execCommand(command, false, value);
    emitChange();
}

function formatBlock(tag) {
    // Toggle back to <p> if the current block already is that tag.
    exec('formatBlock', `<${tag}>`);
}

function addLink() {
    const url = window.prompt('URL do link (inclua https://):', 'https://');
    if (!url) return;
    exec('createLink', url);
    // open links in a new tab
    const sel = window.getSelection();
    const node = sel?.anchorNode?.parentElement;
    if (node && node.tagName === 'A') {
        node.setAttribute('target', '_blank');
        node.setAttribute('rel', 'noopener');
    }
    emitChange();
}

// Paste as plain text (strips Word/Docs junk markup), then let the user format.
function onPaste(e) {
    e.preventDefault();
    const text = (e.clipboardData || window.clipboardData).getData('text/plain');
    document.execCommand('insertText', false, text);
    emitChange();
}

// Keep the DOM in sync when the model changes from outside (initial load / reset),
// but never while the user is typing (would move the caret).
watch(
    () => props.modelValue,
    (val) => {
        const el = editor.value;
        if (el && val !== el.innerHTML) {
            el.innerHTML = val || '';
            syncEmpty();
        }
    }
);

onMounted(() => {
    if (editor.value) {
        editor.value.innerHTML = props.modelValue || '';
        syncEmpty();
    }
});

const tools = [
    { cmd: () => formatBlock('h2'), icon: 'fa-heading', label: 'Título', text: 'H2' },
    { cmd: () => formatBlock('h3'), icon: 'fa-heading', label: 'Subtítulo', text: 'H3' },
    { cmd: () => formatBlock('p'), icon: 'fa-paragraph', label: 'Parágrafo' },
    { sep: true },
    { cmd: () => exec('bold'), icon: 'fa-bold', label: 'Negrito' },
    { cmd: () => exec('italic'), icon: 'fa-italic', label: 'Itálico' },
    { cmd: () => exec('underline'), icon: 'fa-underline', label: 'Sublinhado' },
    { sep: true },
    { cmd: () => exec('insertUnorderedList'), icon: 'fa-list-ul', label: 'Lista' },
    { cmd: () => exec('insertOrderedList'), icon: 'fa-list-ol', label: 'Lista numerada' },
    { sep: true },
    { cmd: () => addLink(), icon: 'fa-link', label: 'Inserir link' },
    { cmd: () => exec('unlink'), icon: 'fa-link-slash', label: 'Remover link' },
    { sep: true },
    { cmd: () => exec('removeFormat'), icon: 'fa-eraser', label: 'Limpar formatação' },
];
</script>

<template>
    <div class="overflow-hidden rounded border border-[#dde6e6] bg-white focus-within:border-brand">
        <!-- Toolbar -->
        <div class="flex flex-wrap items-center gap-0.5 border-b border-[#edf1f1] bg-[#f8fbfb] px-2 py-1.5">
            <template v-for="(t, i) in tools" :key="i">
                <span v-if="t.sep" class="mx-1 h-5 w-px bg-[#dde6e6]"></span>
                <button
                    v-else
                    type="button"
                    :title="t.label"
                    class="grid h-8 min-w-8 place-items-center rounded px-1.5 font-montserrat text-[12px] font-semibold text-[#555] transition hover:bg-[#e8f8f8] hover:text-brand-dark"
                    @mousedown.prevent
                    @click="t.cmd"
                >
                    <span v-if="t.text" class="flex items-center gap-0.5"><i :class="`fa-solid ${t.icon} text-[11px]`"></i>{{ t.text }}</span>
                    <i v-else :class="`fa-solid ${t.icon} text-[13px]`"></i>
                </button>
            </template>
        </div>

        <!-- Editable surface -->
        <div class="relative">
            <div
                ref="editor"
                contenteditable="true"
                class="rte-content prose-admin w-full overflow-y-auto px-4 py-3 font-montserrat text-[15px] leading-relaxed text-[#333] outline-none"
                :style="{ minHeight }"
                @input="emitChange"
                @blur="emitChange"
                @paste="onPaste"
            ></div>
            <p v-if="isEmpty" class="pointer-events-none absolute left-4 top-3 font-montserrat text-[15px] text-[#aab]">
                {{ placeholder }}
            </p>
        </div>
    </div>
</template>

<style scoped>
/* Light prose styling so the editing surface mirrors the public site. */
.prose-admin :deep(h2) {
    font-family: 'Poppins', sans-serif;
    font-size: 22px;
    font-weight: 700;
    margin: 0.6em 0 0.3em;
    color: #363636;
}
.prose-admin :deep(h3) {
    font-family: 'Poppins', sans-serif;
    font-size: 18px;
    font-weight: 700;
    margin: 0.6em 0 0.3em;
    color: #4d4d4d;
}
.prose-admin :deep(p) {
    margin: 0 0 0.8em;
}
.prose-admin :deep(ul) {
    list-style: disc;
    padding-left: 1.4em;
    margin: 0 0 0.8em;
}
.prose-admin :deep(ol) {
    list-style: decimal;
    padding-left: 1.4em;
    margin: 0 0 0.8em;
}
.prose-admin :deep(a) {
    color: #0abab5;
    text-decoration: underline;
}
.prose-admin :deep(strong) {
    font-weight: 700;
}
</style>

<script setup>
defineProps({
    regular: { type: Number, default: 0 },
    sale: { type: Number, default: null },
    current: { type: Number, required: true },
});

function money(cents) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format((cents || 0) / 100);
}
</script>

<template>
    <div class="flex items-baseline justify-center gap-2">
        <span v-if="sale && regular > current" class="font-montserrat text-[14px] text-muted line-through">
            {{ money(regular) }}
        </span>
        <strong class="font-poppins text-[22px] font-semibold text-brand">
            {{ money(current) }}
        </strong>
    </div>
</template>

<script setup>
defineProps({
    children: { type: Array, default: () => [] },
});
</script>

<template>
    <nav v-if="children.length" class="flex flex-wrap justify-center gap-3">
        <a
            v-for="child in children"
            :key="child.slug"
            :href="`/loja/categoria/${child.slug}`"
            class="rounded-[3px] bg-white px-5 py-3 font-poppins text-[14px] font-semibold text-heading-soft shadow-[0_0_10px_rgba(0,0,0,0.14)] transition hover:bg-brand hover:text-white"
        >
            {{ child.name }}
        </a>
    </nav>
</template>

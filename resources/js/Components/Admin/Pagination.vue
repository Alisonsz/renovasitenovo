<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    // Expects a Laravel paginator payload: { links: [...], from, to, total }
    meta: { type: Object, required: true },
});
</script>

<template>
    <nav v-if="meta.links && meta.links.length > 3" class="mt-6 flex flex-wrap items-center justify-between gap-4">
        <p class="font-montserrat text-[13px] text-[#888]">
            Mostrando <strong>{{ meta.from ?? 0 }}</strong>–<strong>{{ meta.to ?? 0 }}</strong>
            de <strong>{{ meta.total ?? 0 }}</strong>
        </p>
        <div class="flex flex-wrap gap-1">
            <template v-for="(link, i) in meta.links" :key="i">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    class="grid h-9 min-w-9 place-items-center rounded-[4px] px-3 font-poppins text-[13px] font-semibold transition"
                    :class="link.active ? 'bg-brand text-white' : 'bg-white text-[#555] ring-1 ring-[#e0e8e8] hover:bg-[#f0fafa]'"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="grid h-9 min-w-9 place-items-center rounded-[4px] px-3 font-poppins text-[13px] text-[#bbb]"
                    v-html="link.label"
                />
            </template>
        </div>
    </nav>
</template>

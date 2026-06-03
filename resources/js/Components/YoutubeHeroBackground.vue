<script setup>
import { computed } from 'vue';

const props = defineProps({
    videoId: { type: String, required: true },
    title: { type: String, default: 'Video de fundo' },
});

const src = computed(() => {
    const params = new URLSearchParams({
        autoplay: '1',
        mute: '1',
        controls: '0',
        playsinline: '1',
        loop: '1',
        playlist: props.videoId,
        modestbranding: '1',
        rel: '0',
        iv_load_policy: '3',
        disablekb: '1',
        fs: '0',
    });

    return `https://www.youtube-nocookie.com/embed/${props.videoId}?${params.toString()}`;
});
</script>

<template>
    <iframe
        class="youtube-hero-bg absolute left-1/2 top-1/2 -z-20 border-0"
        :src="src"
        :title="title"
        allow="autoplay; encrypted-media; picture-in-picture"
        aria-hidden="true"
        tabindex="-1"
    ></iframe>
</template>

<style scoped>
.youtube-hero-bg {
    width: max(100vw, 177.7778vh);
    height: max(56.25vw, 100vh);
    transform: translate(-50%, -50%);
    pointer-events: none;
}
</style>

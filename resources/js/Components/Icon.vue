<script setup>
defineProps({
    name: { type: String, required: true },
    size: { type: [Number, String], default: 24 },
});

// Conjunto de icones de linha (stroke) usados na home.
const STROKE = {
    heart: 'M12 21s-6.7-4.35-9.33-8.07C1.1 10.7 1.5 7.6 3.9 6.2c1.9-1.1 4.1-.5 5.3 1.1L12 10l2.8-2.7c1.2-1.6 3.4-2.2 5.3-1.1 2.4 1.4 2.8 4.5 1.23 6.73C18.7 16.65 12 21 12 21z',
    sparkles: 'M12 3l1.9 4.6L18.5 9.5 13.9 11.4 12 16l-1.9-4.6L5.5 9.5l4.6-1.9L12 3zM19 14l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8L19 14z',
    bolt: 'M13 2L4.5 13.5H11l-1 8.5 8.5-11.5H12l1-8.5z',
    skin: 'M7 11a5 5 0 0 1 10 0c0 3-2 4.5-2 7a3 3 0 0 1-6 0c0-2.5-2-4-2-7z M9.5 11.5h5',
    snow: 'M12 2v20M4.5 6.5l15 11M19.5 6.5l-15 11M12 6l-2.5-2.5M12 6l2.5-2.5M12 18l-2.5 2.5M12 18l2.5 2.5',
    user: 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21a8 8 0 0 1 16 0',
    'map-pin': 'M12 21s7-6.5 7-11a7 7 0 1 0-14 0c0 4.5 7 11 7 11z M12 12a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
    clock: 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM12 7v5l3 2',
    'chevron-left': 'M15 18l-6-6 6-6',
    'chevron-right': 'M9 18l6-6-6-6',
    menu: 'M4 7h16M4 12h16M4 17h16',
    close: 'M6 6l12 12M18 6L6 18',
    instagram: 'M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zM12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7zM17.5 6.5h.01',
};

// Icones que precisam de fill (logos de marca)
const FILL = {
    whatsapp:
        'M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2zm5.8 14.13c-.24.68-1.42 1.32-1.95 1.36-.5.05-.99.24-3.32-.69-2.8-1.1-4.57-3.96-4.71-4.15-.14-.19-1.12-1.49-1.12-2.84 0-1.35.71-2.01.96-2.29.25-.27.55-.34.73-.34.18 0 .37 0 .53.01.17.01.4-.06.62.48.24.57.81 1.96.88 2.1.07.14.12.31.02.5-.09.19-.14.31-.28.48-.14.17-.29.37-.42.5-.14.14-.28.29-.12.57.16.27.71 1.17 1.53 1.9 1.05.93 1.94 1.22 2.21 1.36.27.14.43.12.59-.07.16-.19.68-.79.86-1.06.18-.27.36-.22.61-.13.25.09 1.59.75 1.86.89.27.14.45.2.52.31.07.12.07.66-.17 1.34z',
    tiktok:
        'M16.5 2h-3v13.4a2.6 2.6 0 1 1-2.6-2.6c.2 0 .5 0 .7.1V9.8a5.9 5.9 0 1 0 4.9 5.8V8.9a6.3 6.3 0 0 0 3.7 1.2V7a3.6 3.6 0 0 1-3.6-3.6V2z',
};
</script>

<template>
    <svg
        v-if="FILL[name]"
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="currentColor"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
    >
        <path :d="FILL[name]" />
    </svg>
    <svg
        v-else
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.6"
        stroke-linecap="round"
        stroke-linejoin="round"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
    >
        <path :d="STROKE[name]" />
    </svg>
</template>
